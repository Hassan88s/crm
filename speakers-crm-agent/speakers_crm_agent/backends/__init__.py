"""Backend implementations for the Speakers CRM Agent."""
from .base import (
    AssistantMessage,
    Backend,
    BackendError,
    NormalizedTool,
    ToolCall,
    ToolResult,
    UserMessage,
)

__all__ = [
    "AssistantMessage",
    "Backend",
    "BackendError",
    "NormalizedTool",
    "ToolCall",
    "ToolResult",
    "UserMessage",
]
