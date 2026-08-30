"""Agent tool-execution loop, with a fake backend and a fake MCP client.

Covers: multi-step reasoning, multiple tool calls in one response, retries,
max-step protection.
"""
import pytest

from speakers_crm_agent.backends.base import (
    AssistantMessage, Backend, NormalizedTool, ToolCall, ToolResult, UserMessage,
)
from speakers_crm_agent.mcp_client import MCPClient
from speakers_crm_agent.config import Config
from speakers_crm_agent import agent as agent_mod


class FakeBackend(Backend):
    name = "fake"

    def __init__(self, scripted: list[AssistantMessage]):
        self._scripted = list(scripted)
        self.calls: list[UserMessage] = []

    async def send(self, system, tools, history, new_message):
        self.calls.append(new_message)
        history.append({"role": "user", "content": str(new_message.text or new_message.tool_results)})
        if not self._scripted:
            return AssistantMessage(text="done", stop_reason="end_turn")
        msg = self._scripted.pop(0)
        history.append({"role": "assistant", "content": msg.text or ""})
        return msg

    async def close(self):
        return None


class FakeMCPClient:
    def __init__(self, tools: list[NormalizedTool], responses: dict[str, list]):
        self._tools = tools
        self._responses = {k: list(v) for k, v in responses.items()}
        self.called: list[tuple[str, dict]] = []

    async def __aenter__(self):
        return self

    async def __aexit__(self, *a):
        return None

    @property
    def tools(self):
        return list(self._tools)

    def find_tool(self, name):
        return next((t for t in self._tools if t.name == name), None)

    def validate_arguments(self, name, args):
        if not self.find_tool(name):
            return f"Unknown tool '{name}'."
        return None

    async def call_tool(self, name, arguments):
        self.called.append((name, arguments))
        seq = self._responses.get(name, [])
        if not seq:
            return ToolResult(id="", content={"error": "no_response_scripted"}, is_error=True)
        payload = seq.pop(0)
        if isinstance(payload, Exception):
            return ToolResult(id="", content={"error": str(payload)}, is_error=True)
        return ToolResult(id="", content=payload, is_error=False)


@pytest.fixture(autouse=True)
def _stub_config(monkeypatch):
    monkeypatch.setenv("PULSECORE_API_KEY", "pk_x")
    monkeypatch.setenv("ANTHROPIC_API_KEY", "sk-ant-x")


def _cfg():
    return Config.load()


async def test_single_tool_call_then_final(monkeypatch):
    tools = [NormalizedTool(name="stats", description="", input_schema={})]

    scripted = [
        AssistantMessage(
            text="calling stats",
            tool_calls=[ToolCall(id="c1", name="stats", arguments={})],
            stop_reason="tool_use",
        ),
        AssistantMessage(text="# Report\nEvents: 3", stop_reason="end_turn"),
    ]
    fake_backend = FakeBackend(scripted)
    fake_mcp = FakeMCPClient(tools, {"stats": [{"events": 3}]})

    monkeypatch.setattr(agent_mod, "_make_backend", lambda cfg: fake_backend)
    monkeypatch.setattr(agent_mod, "MCPClient", lambda *a, **kw: fake_mcp)

    out = await agent_mod.run_agent(_cfg(), "give me stats")
    assert "Events: 3" in out
    assert ("stats", {}) in fake_mcp.called


async def test_multiple_tool_calls_in_one_response(monkeypatch):
    tools = [
        NormalizedTool(name="list_events",   description="", input_schema={}),
        NormalizedTool(name="list_speakers", description="", input_schema={}),
    ]
    scripted = [
        AssistantMessage(
            text="",
            tool_calls=[
                ToolCall(id="a", name="list_events",   arguments={}),
                ToolCall(id="b", name="list_speakers", arguments={}),
            ],
            stop_reason="tool_use",
        ),
        AssistantMessage(text="# both fetched", stop_reason="end_turn"),
    ]
    fake_backend = FakeBackend(scripted)
    fake_mcp = FakeMCPClient(tools, {
        "list_events":   [{"data": [1, 2]}],
        "list_speakers": [{"data": [7]}],
    })

    monkeypatch.setattr(agent_mod, "_make_backend", lambda cfg: fake_backend)
    monkeypatch.setattr(agent_mod, "MCPClient", lambda *a, **kw: fake_mcp)

    out = await agent_mod.run_agent(_cfg(), "list both")
    assert "both fetched" in out
    names = [c[0] for c in fake_mcp.called]
    assert names == ["list_events", "list_speakers"]


async def test_max_steps_bounds_loop(monkeypatch):
    tools = [NormalizedTool(name="stats", description="", input_schema={})]
    # Endless tool-call loop.
    scripted = [
        AssistantMessage(
            text="",
            tool_calls=[ToolCall(id=f"c{i}", name="stats", arguments={})],
            stop_reason="tool_use",
        )
        for i in range(50)
    ]
    fake_backend = FakeBackend(scripted)
    fake_mcp = FakeMCPClient(tools, {"stats": [{"n": i} for i in range(50)]})

    monkeypatch.setattr(agent_mod, "_make_backend", lambda cfg: fake_backend)
    monkeypatch.setattr(agent_mod, "MCPClient", lambda *a, **kw: fake_mcp)

    out = await agent_mod.run_agent(_cfg(), "loop forever", max_steps=3)
    assert "Step budget exhausted" in out
    assert len(fake_mcp.called) == 3


async def test_invalid_argument_reports_error_without_execution(monkeypatch):
    tools = [NormalizedTool(
        name="delete_event",
        description="",
        input_schema={"type": "object", "properties": {"event_id": {"type": "integer"}},
                      "required": ["event_id"]},
    )]
    scripted = [
        AssistantMessage(
            text="",
            tool_calls=[ToolCall(id="c1", name="delete_event", arguments={})],  # missing required
            stop_reason="tool_use",
        ),
        AssistantMessage(text="handled", stop_reason="end_turn"),
    ]
    # Use the real MCPClient's validator via a stub that mirrors it.
    class Validating(FakeMCPClient):
        def validate_arguments(self, name, args):
            return MCPClient.validate_arguments(self, name, args)  # type: ignore[arg-type]
        async def call_tool(self, name, arguments):
            err = self.validate_arguments(name, arguments)
            if err:
                return ToolResult(id="", content={"error": "invalid_arguments", "message": err}, is_error=True)
            return await super().call_tool(name, arguments)

    fake_mcp = Validating(tools, {"delete_event": [{"deleted": True}]})
    fake_backend = FakeBackend(scripted)
    monkeypatch.setattr(agent_mod, "_make_backend", lambda cfg: fake_backend)
    monkeypatch.setattr(agent_mod, "MCPClient", lambda *a, **kw: fake_mcp)

    out = await agent_mod.run_agent(_cfg(), "delete some event")
    assert "handled" in out
    # Tool should not have executed successfully.
    assert fake_mcp.called == []
