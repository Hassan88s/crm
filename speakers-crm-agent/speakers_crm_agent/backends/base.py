"""Shared backend interface used by the agent loop.

The agent core knows nothing about Anthropic vs OpenRouter — both backends adapt
their provider-specific request/response shapes into these normalized types.
"""
from __future__ import annotations

from abc import ABC, abstractmethod
from dataclasses import dataclass, field
from typing import Any, List, Optional


class BackendError(RuntimeError):
    """Raised by a backend for provider-side errors that should surface to the loop."""


@dataclass
class NormalizedTool:
    """MCP tool in the neutral form both backends can consume."""
    name: str
    description: str
    input_schema: dict


@dataclass
class ToolCall:
    """A single tool_use / function_call emitted by the model."""
    id: str            # provider-supplied id, needed to correlate the tool_result
    name: str
    arguments: dict


@dataclass
class ToolResult:
    """Payload we hand back to the model for a completed tool call."""
    id: str            # must match the originating ToolCall.id
    content: Any       # dict or string; backend serializes appropriately
    is_error: bool = False


@dataclass
class AssistantMessage:
    """Normalized shape of one assistant response.

    - `text` is any user-visible text emitted alongside tool calls.
    - `tool_calls` is the list of tool_use blocks the model wants executed.
    - `stop_reason` mirrors the provider stop reason ('end_turn', 'tool_use', 'max_tokens', ...).
    - `raw` retains the original assistant message so the backend can echo it back
      correctly on the next turn (Anthropic requires the full assistant content
      including tool_use blocks to be present in the message history).
    """
    text: str = ""
    tool_calls: List[ToolCall] = field(default_factory=list)
    stop_reason: str = ""
    usage: dict = field(default_factory=dict)
    raw: Any = None


@dataclass
class UserMessage:
    """A user-turn message. Either free text OR a list of tool results (not both)."""
    text: Optional[str] = None
    tool_results: List[ToolResult] = field(default_factory=list)


class Backend(ABC):
    """Interface for LLM providers driving the agent."""

    name: str = "base"

    @abstractmethod
    async def send(
        self,
        system: str,
        tools: List[NormalizedTool],
        history: List[Any],
        new_message: UserMessage,
    ) -> AssistantMessage:
        """Send a turn to the provider. Return the normalized assistant response.

        The backend is responsible for appending both `new_message` and its
        assistant reply to `history` in the provider-native format so subsequent
        calls can pass the same list back.
        """

    @abstractmethod
    async def close(self) -> None:
        """Release any HTTP clients / connection pools."""
