"""HTTP wrapper around the Hermes CRM agent.

POST /run       { "task": "...", "dry_run": bool }  -> { "ok", "output", "run_id" }
GET  /health                                          -> { "ok": true }

Auth: Authorization: Bearer <HERMES_API_KEY from .env>
"""
import os
import secrets
import sys
import uuid
from pathlib import Path

from dotenv import load_dotenv
from fastapi import Depends, FastAPI, Header, HTTPException
from pydantic import BaseModel

# Resolve project root without relying on __file__ (some transports strip
# double underscores). systemd sets WorkingDirectory correctly, so cwd works.
ROOT = Path(os.environ.get("HERMES_ROOT") or Path.cwd()).resolve()
load_dotenv(ROOT / ".env")

KEY = os.environ.get("HERMES_API_KEY") or secrets.token_urlsafe(32)
os.environ["HERMES_API_KEY"] = KEY

sys.path.insert(0, str(ROOT))
from hermes_crm.agent import run_agent  # noqa: E402
from hermes_crm.config import Settings  # noqa: E402

app = FastAPI(title="Hermes CRM Agent API")


class RunRequest(BaseModel):
    task: str
    dry_run: bool = False
    max_steps: int | None = None


def _auth(authorization: str = Header(default="")):
    if not authorization.startswith("Bearer ") or authorization[7:].strip() != KEY:
        raise HTTPException(status_code=401, detail="unauthorized")


@app.get("/health")
def health():
    return {"ok": True, "run_dir": str(ROOT / "runs")}


@app.post("/run", dependencies=[Depends(_auth)])
async def run(req: RunRequest):
    run_id = uuid.uuid4().hex[:12]
    try:
        settings = Settings.load()
        output = await run_agent(
            settings, req.task, dry_run=req.dry_run, max_steps=req.max_steps,
        )
        return {"ok": True, "run_id": run_id, "output": output}
    except Exception as e:
        err_type = getattr(type(e), "__name__", "Error")
        return {"ok": False, "run_id": run_id, "error": f"{err_type}: {e}"}


# No `if __name__ == "__main__":` guard on purpose — this file is intended to be
# launched directly by systemd or `python hermes_api.py`, never imported.
def _entry():
    print(f"HERMES_API_KEY = {KEY}")
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8788)


if os.environ.get("HERMES_API_AUTOSTART", "1") == "1" and __package__ in (None, ""):
    # Runs only when invoked as a script, not when imported (tests etc.).
    _entry()
