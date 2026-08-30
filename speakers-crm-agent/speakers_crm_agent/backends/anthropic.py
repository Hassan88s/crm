"""Anthropic Messages API backend for Claude Opus 5.

Uses the official `anthropic` SDK. Speaks Claude's native tool_use / tool_result
content blocks — no XML, no OpenAI compatibility shim.
"""
from __future__ import annotations

import json
from typing import Any, List

from anthropic import AsyncAnthropic

from .base import (
    AssistantMessage,
    Backend,
    BackendError,
    NormalizedTool,
    ToolCall,
    UserMessage,
)


class AnthropicBackend(Backend):
    name = "anthropic"

    def __init__(
        self,
        api_key: str,
        model: str = "claude-opus-5",
        base_url: str = "https://api.anthropic.com",
        max_tokens: int = 4096,
        http_timeout_seconds: int = 120,
    ):
        if not api_key:
            raise BackendError("ANTHROPIC_API_KEY is required for the anthropic backend.")
        self.model = model
        self.max_tokens = max_tokens
        self._client = AsyncAnthropic(
            api_key=api_key,
            base_url=base_url,
            timeout=http_timeout_seconds,
        )

    async def close(self) -> None:
        try:
            await self._client.close()
        except Exception:
            pass

    def _tools_for_provider(self, tools: List[NormalizedTool]) -> list[dict]:
        return [
            {
                "name": t.name,
                "description": t.description or "",
                "input_schema": t.input_schema or {"type": "object", "properties": {}},
            }
            for t in tools
        ]

    def _user_message_to_provider(self, msg: UserMessage) -> dict:
        if msg.tool_results:
            return {
                "role": "user",
                "content": [
                    {
                        "type": "tool_result",
                        "tool_use_id": tr.id,
                        "content": tr.content if isinstance(tr.content, str)
                        else json.dumps(tr.content, ensure_ascii=False, default=str),
                        "is_error": tr.is_error,
                    }
                    for tr in msg.tool_results
                ],
            }
        return {"role": "user", "content": msg.text or ""}

    async def send(
        self,
        system: str,
        tools: List[NormalizedTool],
        history: List[Any],
        new_message: UserMessage,
    ) -> AssistantMessage:
        provider_msg = self._user_message_to_provider(new_message)
        history.append(provider_msg)

        try:
            resp = await self._client.messages.create(
                model=self.model,
                system=system,
                tools=self._tools_for_provider(tools) if tools else [],
                messages=history,
                max_tokens=self.max_tokens,
            )
        except Exception as e:
            # Roll back the provisional message so the caller can retry cleanly.
            history.pop()
            raise BackendError(f"Anthropic request failed: {type(e).__name__}: {e}") from e

        text_parts: list[str] = []
        tool_calls: list[ToolCall] = []
        assistant_content: list[dict] = []

        for block in resp.content:
            if getattr(block, "type", None) == "text":
                text_parts.append(block.text)
                assistant_content.append({"type": "text", "text": block.text})
            elif getattr(block, "type", None) == "tool_use":
                tool_calls.append(ToolCall(
                    id=block.id,
                    name=block.name,
                    arguments=dict(block.input or {}),
                ))
                assistant_content.append({
                    "type": "tool_use",
                    "id": block.id,
                    "name": block.name,
                    "input": block.input,
                })

        # Append the exact assistant turn so tool_result blocks correlate.
        history.append({"role": "assistant", "content": assistant_content})

        usage = {}
        if getattr(resp, "usage", None) is not None:
            usage = {
                "input_tokens":  getattr(resp.usage, "input_tokens",  None),
                "output_tokens": getattr(resp.usage, "output_tokens", None),
            }

        return AssistantMessage(
            text="".join(text_parts),
            tool_calls=tool_calls,
            stop_reason=resp.stop_reason or "",
            usage=usage,
            raw=assistant_content,
        )
