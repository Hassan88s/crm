"""Config loading + validation. No secrets should ever appear in exception messages."""
import pytest

from speakers_crm_agent.config import Config, ConfigError


def test_default_backend_is_anthropic(monkeypatch):
    for k in ("LLM_BACKEND", "ANTHROPIC_API_KEY", "OPENROUTER_API_KEY", "PULSECORE_API_KEY"):
        monkeypatch.delenv(k, raising=False)
    monkeypatch.setenv("PULSECORE_API_KEY", "pk_deadbeef_test")
    monkeypatch.setenv("ANTHROPIC_API_KEY", "sk-ant-testtesttesttest")
    cfg = Config.load()
    assert cfg.backend == "anthropic"
    cfg.validate()


def test_unknown_backend_rejected(monkeypatch):
    monkeypatch.setenv("LLM_BACKEND", "nope")
    with pytest.raises(ConfigError):
        Config.load()


def test_missing_pulsecore_key_raises(monkeypatch):
    monkeypatch.setenv("LLM_BACKEND", "anthropic")
    monkeypatch.setenv("ANTHROPIC_API_KEY", "sk-ant-x")
    monkeypatch.delenv("PULSECORE_API_KEY", raising=False)
    cfg = Config.load()
    with pytest.raises(ConfigError) as exc:
        cfg.validate()
    # The error names the var but not its (empty) value.
    assert "PULSECORE_API_KEY" in str(exc.value)
    assert "sk-ant-x" not in str(exc.value)


def test_openrouter_requires_its_own_key(monkeypatch):
    monkeypatch.setenv("LLM_BACKEND", "openrouter")
    monkeypatch.setenv("PULSECORE_API_KEY", "pk_x")
    monkeypatch.delenv("OPENROUTER_API_KEY", raising=False)
    cfg = Config.load()
    with pytest.raises(ConfigError) as exc:
        cfg.validate()
    assert "OPENROUTER_API_KEY" in str(exc.value)


def test_mcp_subprocess_env_strips_llm_keys(monkeypatch):
    monkeypatch.setenv("LLM_BACKEND", "anthropic")
    monkeypatch.setenv("ANTHROPIC_API_KEY", "sk-ant-secret")
    monkeypatch.setenv("OPENROUTER_API_KEY", "sk-or-secret")
    monkeypatch.setenv("PULSECORE_API_KEY", "pk_ok")
    cfg = Config.load()
    env = cfg.mcp_subprocess_env()
    assert "ANTHROPIC_API_KEY" not in env
    assert "OPENROUTER_API_KEY" not in env
    assert env["PULSECORE_API_KEY"] == "pk_ok"
    assert env["PULSECORE_API_BASE"].startswith("https://")


def test_mcp_command_is_split(monkeypatch):
    monkeypatch.setenv("MCP_SERVER_CMD", "python foo/bar.py --flag")
    monkeypatch.setenv("PULSECORE_API_KEY", "pk_x")
    monkeypatch.setenv("ANTHROPIC_API_KEY", "sk-ant-x")
    cfg = Config.load()
    assert cfg.mcp_server_cmd[:2] == ["python", "foo/bar.py"]
