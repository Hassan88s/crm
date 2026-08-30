"""Runtime configuration loaded from environment (or a .env file).

Validates required values without ever printing their content.
"""
from __future__ import annotations

import os
import shlex
from dataclasses import dataclass, field
from pathlib import Path
from typing import List, Literal

from dotenv import load_dotenv

Backend = Literal["anthropic", "openrouter"]


class ConfigError(RuntimeError):
    """Raised when configuration is missing or invalid. Message never contains secret values."""


@dataclass(frozen=True)
class Config:
    backend: Backend
    # Anthropic
    anthropic_api_key: str
    anthropic_model: str
    anthropic_base_url: str
    # OpenRouter
    openrouter_api_key: str
    openrouter_model: str
    openrouter_base_url: str
    openrouter_app_name: str
    openrouter_site_url: str
    # CRM / MCP
    pulsecore_api_base: str
    pulsecore_api_key: str
    mcp_server_cmd: List[str] = field(default_factory=list)
    # Agent controls
    max_steps: int = 30
    http_timeout_seconds: int = 120
    max_tool_retries: int = 3
    log_level: str = "INFO"

    @classmethod
    def load(cls, env_file: str | None = None) -> "Config":
        """Load from environment, optionally reading a .env file first."""
        if env_file:
            load_dotenv(env_file, override=False)
        else:
            # Try common locations quietly.
            for candidate in (Path(".env"), Path(__file__).resolve().parents[1] / ".env"):
                if candidate.exists():
                    load_dotenv(candidate, override=False)
                    break

        backend = os.environ.get("LLM_BACKEND", "anthropic").strip().lower()
        if backend not in ("anthropic", "openrouter"):
            raise ConfigError(
                f"LLM_BACKEND must be 'anthropic' or 'openrouter'; got '{backend}'."
            )

        cfg = cls(
            backend=backend,  # type: ignore[arg-type]
            anthropic_api_key=os.environ.get("ANTHROPIC_API_KEY", ""),
            anthropic_model=os.environ.get("ANTHROPIC_MODEL", "claude-opus-5"),
            anthropic_base_url=os.environ.get("ANTHROPIC_BASE_URL", "https://api.anthropic.com"),
            openrouter_api_key=os.environ.get("OPENROUTER_API_KEY", ""),
            openrouter_model=os.environ.get("OPENROUTER_MODEL", "anthropic/claude-opus-5"),
            openrouter_base_url=os.environ.get("OPENROUTER_BASE_URL", "https://openrouter.ai/api/v1"),
            openrouter_app_name=os.environ.get("OPENROUTER_APP_NAME", "Speakers CRM Agent"),
            openrouter_site_url=os.environ.get("OPENROUTER_SITE_URL", "https://crm-speakers.com"),
            pulsecore_api_base=os.environ.get("PULSECORE_API_BASE", "https://crm-speakers.com/api/v1"),
            pulsecore_api_key=os.environ.get("PULSECORE_API_KEY", ""),
            mcp_server_cmd=shlex.split(os.environ.get(
                "MCP_SERVER_CMD", "python mcp-server/pulsecore_mcp.py"
            )),
            max_steps=int(os.environ.get("AGENT_MAX_STEPS", "30")),
            http_timeout_seconds=int(os.environ.get("HTTP_TIMEOUT_SECONDS", "120")),
            max_tool_retries=int(os.environ.get("MAX_TOOL_RETRIES", "3")),
            log_level=os.environ.get("LOG_LEVEL", "INFO").upper(),
        )
        return cfg

    def validate(self) -> None:
        """Raise ConfigError with a redacted message if required values are absent."""
        missing: list[str] = []
        if self.backend == "anthropic" and not self.anthropic_api_key:
            missing.append("ANTHROPIC_API_KEY")
        if self.backend == "openrouter" and not self.openrouter_api_key:
            missing.append("OPENROUTER_API_KEY")
        if not self.pulsecore_api_key:
            missing.append("PULSECORE_API_KEY")
        if not self.mcp_server_cmd:
            missing.append("MCP_SERVER_CMD")
        if missing:
            raise ConfigError(
                "Missing required environment variables: " + ", ".join(missing) +
                ". Copy .env.example to .env and fill them in. "
                "Values are read from env only; nothing is printed here."
            )

    def mcp_subprocess_env(self) -> dict[str, str]:
        """Environment dict passed to the MCP subprocess. Never includes LLM keys."""
        env = os.environ.copy()
        env["PULSECORE_API_BASE"] = self.pulsecore_api_base
        env["PULSECORE_API_KEY"] = self.pulsecore_api_key
        # Prevent leaking the LLM keys into the MCP subprocess.
        env.pop("ANTHROPIC_API_KEY", None)
        env.pop("OPENROUTER_API_KEY", None)
        return env
