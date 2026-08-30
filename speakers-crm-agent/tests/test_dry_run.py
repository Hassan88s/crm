"""Dry-run must block mutating tools and still allow read-only discovery."""
import json

import pytest

from speakers_crm_agent import agent as agent_mod
from speakers_crm_agent.backends.base import (
    AssistantMessage, ToolCall,
)
from speakers_crm_agent.config import Config
from speakers_crm_agent.mcp_client import is_mutating_tool
from tests.test_tool_call_loop import FakeBackend, FakeMCPClient


@pytest.fixture(autouse=True)
def _stub_config(monkeypatch):
    monkeypatch.setenv("PULSECORE_API_KEY", "pk_x")
    monkeypatch.setenv("ANTHROPIC_API_KEY", "sk-ant-x")


def test_mutating_name_heuristic():
    assert is_mutating_tool("delete_speaker")
    assert is_mutating_tool("resend_failed_recipients")
    assert is_mutating_tool("send_reply")
    assert is_mutating_tool("import_scraped")
    assert not is_mutating_tool("list_campaigns")
    assert not is_mutating_tool("get_event")
    assert not is_mutating_tool("stats")
    assert not is_mutating_tool("whoami")
    assert not is_mutating_tool("discover_events")


async def test_dry_run_blocks_mutating_tool(monkeypatch):
    from speakers_crm_agent.backends.base import NormalizedTool
    tools = [
        NormalizedTool(name="get_campaign",              description="", input_schema={}),
        NormalizedTool(name="resend_failed_recipients",  description="",
                       input_schema={"type": "object",
                                     "properties": {"campaign_id": {"type": "integer"}},
                                     "required": ["campaign_id"]}),
    ]
    scripted = [
        AssistantMessage(
            text="Reading campaign then proposing resend.",
            tool_calls=[
                ToolCall(id="a", name="get_campaign",             arguments={"campaign_id": 12}),
                ToolCall(id="b", name="resend_failed_recipients", arguments={"campaign_id": 12}),
            ],
            stop_reason="tool_use",
        ),
        AssistantMessage(text="Would resend 93425 recipients on campaign 12.", stop_reason="end_turn"),
    ]
    fake_backend = FakeBackend(scripted)
    fake_mcp = FakeMCPClient(tools, {
        "get_campaign":             [{"id": 12, "status": "paused", "failed_count": 93425}],
        "resend_failed_recipients": [{"should_not": "run"}],
    })
    monkeypatch.setattr(agent_mod, "_make_backend", lambda cfg: fake_backend)
    monkeypatch.setattr(agent_mod, "MCPClient", lambda *a, **kw: fake_mcp)

    out = await agent_mod.run_agent(Config.load(), "resend failed on campaign 12", dry_run=True)

    # Read-only tool DID execute; mutating tool did NOT.
    called_names = [c[0] for c in fake_mcp.called]
    assert "get_campaign" in called_names
    assert "resend_failed_recipients" not in called_names
    # Report is labeled and includes the proposed tool call.
    assert "DRY RUN" in out
    assert "resend_failed_recipients" in out
    # JSON block for the proposed arguments.
    assert '"campaign_id": 12' in out
