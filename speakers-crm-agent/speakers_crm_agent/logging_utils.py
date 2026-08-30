"""JSONL run logging + recursive secret redaction.

Every agent run writes one file: runs/{UTC_TIMESTAMP}.jsonl
"""
from __future__ import annotations

import json
import logging
import re
import time
import uuid
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Optional

# Keys whose *values* must never be logged, regardless of their content.
SECRET_KEY_PATTERN = re.compile(
    r"(?i)(api[_-]?key|authorization|password|secret|token|bearer|"
    r"anthropic|openrouter|pulsecore|xai|deepseek)"
)

# Value-shape patterns that indicate a secret even under a benign key name.
SECRET_VALUE_PATTERNS = [
    re.compile(r"^Bearer\s+.+", re.IGNORECASE),                # Authorization header
    re.compile(r"^sk-[A-Za-z0-9_\-]{16,}$"),                   # generic OpenAI-shaped
    re.compile(r"^sk-ant-[A-Za-z0-9_\-]{16,}$"),               # Anthropic
    re.compile(r"^sk-or-[A-Za-z0-9_\-]{16,}$"),                # OpenRouter
    re.compile(r"^pk_[A-Za-z0-9]{16,}$"),                      # PulseCore
]

REDACTED = "***REDACTED***"


def _redact_value(value: Any) -> Any:
    if isinstance(value, str):
        for pat in SECRET_VALUE_PATTERNS:
            if pat.match(value.strip()):
                return REDACTED
    return value


def redact(obj: Any) -> Any:
    """Deep-copy `obj` with secret keys and secret-shaped values scrubbed.

    Never raises. Handles dicts, lists, tuples, and unknown objects (best-effort str()).
    """
    if isinstance(obj, dict):
        out: dict[str, Any] = {}
        for k, v in obj.items():
            if isinstance(k, str) and SECRET_KEY_PATTERN.search(k):
                out[k] = REDACTED
            else:
                out[k] = redact(v)
        return out
    if isinstance(obj, list):
        return [redact(v) for v in obj]
    if isinstance(obj, tuple):
        return [redact(v) for v in obj]
    return _redact_value(obj)


def _json_safe(obj: Any) -> Any:
    """Coerce anything JSON-encodable, or fall back to a repr string."""
    try:
        json.dumps(obj)
        return obj
    except (TypeError, ValueError):
        if isinstance(obj, dict):
            return {str(k): _json_safe(v) for k, v in obj.items()}
        if isinstance(obj, (list, tuple)):
            return [_json_safe(v) for v in obj]
        return repr(obj)


class RunLogger:
    """Structured JSONL logger for one agent run."""

    def __init__(self, log_dir: str | Path = "runs", run_id: str | None = None, level: str = "INFO"):
        self.log_dir = Path(log_dir)
        self.log_dir.mkdir(parents=True, exist_ok=True)
        self.run_id = run_id or uuid.uuid4().hex[:12]
        ts = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
        self.path = self.log_dir / f"{ts}_{self.run_id}.jsonl"
        self.step = 0
        self._fh = self.path.open("a", encoding="utf-8")
        self.logger = logging.getLogger("speakers_crm_agent")
        self.logger.setLevel(getattr(logging, level, logging.INFO))

    def close(self) -> None:
        try:
            self._fh.close()
        except Exception:
            pass

    def next_step(self) -> int:
        self.step += 1
        return self.step

    def log(self, record_type: str, **fields: Any) -> None:
        record = {
            "ts": datetime.now(timezone.utc).isoformat(),
            "run_id": self.run_id,
            "step": self.step,
            "type": record_type,
            **{k: redact(_json_safe(v)) for k, v in fields.items()},
        }
        line = json.dumps(record, ensure_ascii=False, default=str)
        self._fh.write(line + "\n")
        self._fh.flush()
        self.logger.debug("%s %s", record_type, line)

    def timed(self, record_type: str, **fields: Any) -> "_Timer":
        return _Timer(self, record_type, fields)


class _Timer:
    def __init__(self, run_logger: RunLogger, record_type: str, fields: dict[str, Any]):
        self.run_logger = run_logger
        self.record_type = record_type
        self.fields = fields
        self.start: Optional[float] = None
        self.success = True
        self.error: Optional[str] = None

    def __enter__(self) -> "_Timer":
        self.start = time.perf_counter()
        return self

    def __exit__(self, exc_type, exc, tb) -> None:
        duration_ms = int(((time.perf_counter() or 0) - (self.start or 0)) * 1000)
        if exc_type is not None:
            self.success = False
            self.error = f"{exc_type.__name__}: {exc}"
        self.run_logger.log(
            self.record_type,
            duration_ms=duration_ms,
            success=self.success,
            error=self.error,
            **self.fields,
        )
