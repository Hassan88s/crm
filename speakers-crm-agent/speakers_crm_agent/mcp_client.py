"""MCP client — starts the pulsecore_mcp server over stdio, discovers tools,
and calls them on behalf of the agent.

The client keeps the neutral `NormalizedTool` shape; conversion to Anthropic /
OpenRouter formats happens inside the backends, so any MCP server that speaks the
protocol can be plugged in without touching backend code.
"""
from __future__ import annotations

import json
from contextlib import AsyncExitStack
from typing import Any, Dict, List, Optional

from mcp import ClientSession, StdioServerParameters
from mcp.client.stdio import stdio_client

from .backends.base import NormalizedTool, ToolResult


# Tools that must never execute in dry-run mode. The name-prefix heuristic keeps
# us out of trouble even when the MCP server later adds new mutating tools we
# don't know about yet.
MUTATING_NAME_PREFIXES = (
    "create_", "update_", "delete_", "revoke_", "toggle_",
    "start_", "pause_", "resume_",
    "resend_", "send_",
    "mark_", "move_",
    "change_", "reclassify_",
    "import_", "scrape_",
    "fetch_",  # fetch_new_replies mutates DB via IMAP pull
)

READONLY_NAME_ALLOWLIST_PREFIXES = (
    "list_", "get_", "search_",
    "whoami", "stats",
    "discover_",  # scraper.discover is a network read
)


def is_mutating_tool(name: str) -> bool:
    """Heuristic: a tool name mutates unless it is clearly read-only."""
    for pref in READONLY_NAME_ALLOWLIST_PREFIXES:
        if name.startswith(pref):
            return False
    for pref in MUTATING_NAME_PREFIXES:
        if name.startswith(pref):
            return True
    # Unknown shape — be safe.
    return True


class MCPClient:
    """Thin async wrapper around mcp.ClientSession + stdio_client."""

    def __init__(self, command: List[str], env: Optional[Dict[str, str]] = None):
        if not command:
            raise ValueError("MCP server command is empty.")
        self._params = StdioServerParameters(command=command[0], args=command[1:], env=env)
        self._session: Optional[ClientSession] = None
        self._stack: Optional[AsyncExitStack] = None
        self._tools: List[NormalizedTool] = []

    async def __aenter__(self) -> "MCPClient":
        # AsyncExitStack keeps enter+exit in the same task, which anyio's cancel
        # scope requires. Nested `async with … async with` across method calls
        # crosses task boundaries under some event-loop configurations.
        self._stack = AsyncExitStack()
        await self._stack.__aenter__()
        try:
            read, write = await self._stack.enter_async_context(stdio_client(self._params))
            self._session = await self._stack.enter_async_context(ClientSession(read, write))
            await self._session.initialize()
            await self._discover_tools()
        except Exception:
            await self._stack.__aexit__(None, None, None)
            self._stack = None
            raise
        return self

    async def __aexit__(self, exc_type, exc, tb) -> None:
        if self._stack is not None:
            try:
                await self._stack.__aexit__(exc_type, exc, tb)
            finally:
                self._stack = None

    async def _discover_tools(self) -> None:
        assert self._session is not None
        result = await self._session.list_tools()
        self._tools = [
            NormalizedTool(
                name=t.name,
                description=t.description or "",
                input_schema=(t.inputSchema or {"type": "object", "properties": {}}),
            )
            for t in result.tools
        ]

    @property
    def tools(self) -> List[NormalizedTool]:
        return list(self._tools)

    def find_tool(self, name: str) -> Optional[NormalizedTool]:
        for t in self._tools:
            if t.name == name:
                return t
        return None

    def validate_arguments(self, tool_name: str, args: dict) -> Optional[str]:
        """Return None if OK, otherwise a human-readable error string.

        Full JSON-schema validation would be nicer, but pulling jsonschema in
        just for `required` + `properties` checks is overkill. We check the two
        things that catch 95% of hallucinations.
        """
        tool = self.find_tool(tool_name)
        if tool is None:
            return f"Unknown tool '{tool_name}'."
        schema = tool.input_schema or {}
        props = schema.get("properties") or {}
        required = schema.get("required") or []
        if props:
            unknown = [k for k in args.keys() if k not in props]
            if unknown:
                return f"Tool '{tool_name}' received unknown arguments: {unknown}."
        missing = [k for k in required if k not in args]
        if missing:
            return f"Tool '{tool_name}' is missing required arguments: {missing}."
        return None

    async def call_tool(self, name: str, arguments: dict) -> ToolResult:
        """Execute an MCP tool. Never raises — returns a ToolResult with is_error=True on failure."""
        assert self._session is not None
        err = self.validate_arguments(name, arguments)
        if err:
            return ToolResult(id="", content={"error": "invalid_arguments", "message": err}, is_error=True)
        try:
            resp = await self._session.call_tool(name, arguments or {})
        except Exception as e:
            return ToolResult(
                id="",
                content={"error": type(e).__name__, "message": str(e)},
                is_error=True,
            )
        # Flatten mcp.CallToolResult.content (list of Content blocks) into a JSON-ish payload.
        payload: Any
        blocks_text: list[str] = []
        for block in getattr(resp, "content", []) or []:
            t = getattr(block, "type", None)
            if t == "text":
                blocks_text.append(getattr(block, "text", ""))
            else:
                # Unknown block type — dump its dict.
                try:
                    blocks_text.append(json.dumps(block.model_dump()))
                except Exception:
                    blocks_text.append(repr(block))
        joined = "\n".join(blocks_text).strip()
        if joined:
            try:
                payload = json.loads(joined)
            except json.JSONDecodeError:
                payload = joined
        else:
            payload = {}
        return ToolResult(id="", content=payload, is_error=bool(getattr(resp, "isError", False)))
