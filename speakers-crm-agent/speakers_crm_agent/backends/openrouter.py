"""OpenRouter backend for `anthropic/claude-opus-5`.

Talks OpenRouter's OpenAI-compatible chat/completions endpoint using httpx.
Does NOT install or use the OpenAI Python SDK.
"""
from __future__ import annotations

import json
from typing import Any, List

import httpx

from .base import (
    AssistantMessage,
    Backend,
    BackendError,
    NormalizedTool,
    ToolCall,
    UserMessage,
)


class OpenRouterBackend(Backend):
    name = "openrouter"

    def __init__(
        self,
        api_key: str,
        model: str = "anthropic/claude-opus-5",
        base_url: str = "https://openrouter.ai/api/v1",
        app_name: str = "Speakers CRM Agent",
        site_url: str = "https://crm-speakers.com",
        max_tokens: int = 4096,
        http_timeout_seconds: int = 120,
    ):
        if not api_key:
            raise BackendError("OPENROUTER_API_KEY is required for the openrouter backend.")
        self.model = model
        self.max_tokens = max_tokens
        self._client = httpx.AsyncClient(
            base_url=base_url.rstrip("/"),
            headers={
                "Authorization": f"Bearer {api_key}",
                "Content-Type": "application/json",
                "HTTP-Referer": site_url,
                "X-Title": app_name,
            },
            timeout=http_timeout_seconds,
        )

    async def close(self) -> None:
        try:
            await self._client.aclose()
        except Exception:
            pass

    def _tools_for_provider(self, tools: List[NormalizedTool]) -> list[dict]:
        return [
            {
                "type": "function",
                "function": {
                    "name": t.name,
                    "description": t.description or "",
                    "parameters": t.input_schema or {"type": "object", "properties": {}},
                },
            }
            for t in tools
        ]

    def _user_message_to_provider(self, msg: UserMessage) -> list[dict]:
        if msg.tool_results:
            # OpenAI-style: one message per tool result, role="tool".
            return [
                {
                    "role": "tool",
                    "tool_call_id": tr.id,
                    "content": tr.content if isinstance(tr.content, str)
                    else json.dumps(tr.content, ensure_ascii=False, default=str),
                }
                for tr in msg.tool_results
            ]
        return [{"role": "user", "content": msg.text or ""}]

    async def send(
        self,
        system: str,
        tools: List[NormalizedTool],
        history: List[Any],
        new_message: UserMessage,
    ) -> AssistantMessage:
        # Ensure history starts with the system prompt exactly once.
        if not history or history[0].get("role") != "system":
            history.insert(0, {"role": "system", "content": system})

        new_provider_msgs = self._user_message_to_provider(new_message)
        history.extend(new_provider_msgs)

        payload = {
            "model": self.model,
            "messages": history,
            "max_tokens": self.max_tokens,
        }
        if tools:
            payload["tools"] = self._tools_for_provider(tools)
            payload["tool_choice"] = "auto"

        try:
            r = await self._client.post("/chat/completions", json=payload)
        except httpx.HTTPError as e:
            for _ in new_provider_msgs:
                history.pop()
            raise BackendError(f"OpenRouter transport failed: {e}") from e

        if r.status_code >= 400:
            for _ in new_provider_msgs:
                history.pop()
            raise BackendError(
                f"OpenRouter HTTP {r.status_code}: {r.text[:500]}"
            )

        data = r.json()
        try:
            choice = data["choices"][0]
        except (KeyError, IndexError) as e:
            raise BackendError(f"OpenRouter returned no choices: {json.dumps(data)[:400]}") from e

        message = choice.get("message") or {}
        text = message.get("content") or ""
        tool_calls_raw = message.get("tool_calls") or []

        tool_calls: list[ToolCall] = []
        for tc in tool_calls_raw:
            fn = tc.get("function") or {}
            raw_args = fn.get("arguments", "{}")
            try:
                args = json.loads(raw_args) if isinstance(raw_args, str) else dict(raw_args)
            except json.JSONDecodeError:
                args = {"_raw": raw_args}
            tool_calls.append(ToolCall(
                id=tc.get("id") or "",
                name=fn.get("name") or "",
                arguments=args,
            ))

        # Append the assistant turn in provider-native form.
        assistant_entry: dict[str, Any] = {"role": "assistant", "content": text}
        if tool_calls_raw:
            assistant_entry["tool_calls"] = tool_calls_raw
        history.append(assistant_entry)

        usage = data.get("usage") or {}
        return AssistantMessage(
            text=text,
            tool_calls=tool_calls,
            stop_reason=choice.get("finish_reason") or "",
            usage={
                "input_tokens":  usage.get("prompt_tokens"),
                "output_tokens": usage.get("completion_tokens"),
            },
            raw=assistant_entry,
        )
