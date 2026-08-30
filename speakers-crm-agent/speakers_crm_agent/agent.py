"""Speakers CRM Agent — main entrypoint.

Usage:
    python -m speakers_crm_agent.agent --task "Resend failed on campaign 12"
    echo "list campaigns" | python -m speakers_crm_agent.agent

Options:
    --dry-run       Block mutating MCP tool executions; show proposed calls only.
    --backend NAME  Override LLM_BACKEND (anthropic | openrouter).
    --env FILE      Load a specific .env file.
    --max-steps N   Override AGENT_MAX_STEPS.
"""
from __future__ import annotations

import argparse
import asyncio
import json
import sys
from typing import List, Optional

from .backends.base import Backend, BackendError, ToolCall, ToolResult, UserMessage
from .config import Config, ConfigError
from .logging_utils import RunLogger, redact
from .mcp_client import MCPClient, is_mutating_tool
from .prompts import build_system_prompt


def _make_backend(cfg: Config) -> Backend:
    if cfg.backend == "anthropic":
        from .backends.anthropic import AnthropicBackend
        return AnthropicBackend(
            api_key=cfg.anthropic_api_key,
            model=cfg.anthropic_model,
            base_url=cfg.anthropic_base_url,
            http_timeout_seconds=cfg.http_timeout_seconds,
        )
    if cfg.backend == "openrouter":
        from .backends.openrouter import OpenRouterBackend
        return OpenRouterBackend(
            api_key=cfg.openrouter_api_key,
            model=cfg.openrouter_model,
            base_url=cfg.openrouter_base_url,
            app_name=cfg.openrouter_app_name,
            site_url=cfg.openrouter_site_url,
            http_timeout_seconds=cfg.http_timeout_seconds,
        )
    raise ConfigError(f"Unknown backend: {cfg.backend}")


async def run_agent(
    cfg: Config,
    task: str,
    *,
    dry_run: bool = False,
    max_steps: Optional[int] = None,
) -> str:
    """Run one agent task. Returns the final Markdown summary."""
    log = RunLogger(level=cfg.log_level)
    limit = max_steps or cfg.max_steps
    proposed_tool_calls: list[dict] = []

    log.log(
        "run_started",
        backend=cfg.backend,
        model=(cfg.anthropic_model if cfg.backend == "anthropic" else cfg.openrouter_model),
        dry_run=dry_run,
        max_steps=limit,
        task=task,
    )

    backend = _make_backend(cfg)
    system = build_system_prompt(dry_run=dry_run)

    final_text = ""
    history: List = []

    try:
        async with MCPClient(cfg.mcp_server_cmd, env=cfg.mcp_subprocess_env()) as mcp:
            log.log("mcp_initialized", server_cmd=cfg.mcp_server_cmd)
            tools = mcp.tools
            log.log("tools_discovered",
                    count=len(tools),
                    names=[t.name for t in tools])

            new_msg: UserMessage = UserMessage(text=task)

            for step in range(1, limit + 1):
                log.next_step()
                try:
                    with log.timed("model_request", backend=cfg.backend):
                        assistant = await backend.send(system, tools, history, new_msg)
                except BackendError as e:
                    log.log("run_failed", error=str(e))
                    return f"# ❌ Agent failed\n\nBackend error: {e}"

                log.log("model_response",
                        text_preview=(assistant.text or "")[:400],
                        tool_call_count=len(assistant.tool_calls),
                        stop_reason=assistant.stop_reason,
                        usage=assistant.usage)

                if not assistant.tool_calls:
                    # Model is done reasoning.
                    final_text = assistant.text or "(no text returned)"
                    break

                # Execute all requested tool calls.
                tool_results: list[ToolResult] = []
                for call in assistant.tool_calls:
                    is_mut = is_mutating_tool(call.name)
                    log.log("tool_call",
                            tool=call.name,
                            arguments=call.arguments,
                            is_mutating=is_mut,
                            dry_run=dry_run)

                    if dry_run and is_mut:
                        proposed = {
                            "tool": call.name,
                            "arguments": call.arguments,
                            "blocked_by": "dry_run",
                        }
                        proposed_tool_calls.append(proposed)
                        tool_results.append(ToolResult(
                            id=call.id,
                            content={
                                "dry_run": True,
                                "blocked": True,
                                "would_call": call.name,
                                "with_arguments": call.arguments,
                                "reason": "Mutating tools are blocked in --dry-run mode.",
                            },
                            is_error=False,
                        ))
                        continue

                    result: Optional[ToolResult] = None
                    last_err: Optional[str] = None
                    for attempt in range(1, cfg.max_tool_retries + 1):
                        with log.timed("tool_result", tool=call.name, attempt=attempt):
                            r = await mcp.call_tool(call.name, call.arguments)
                        if not r.is_error:
                            result = r
                            break
                        last_err = str(r.content)
                        log.log("retry", tool=call.name, attempt=attempt, error=last_err[:200])
                        # Bounded exponential backoff: 0.5s, 1s, 2s...
                        await asyncio.sleep(0.5 * (2 ** (attempt - 1)))
                    if result is None:
                        result = ToolResult(
                            id=call.id,
                            content={"error": "max_retries_exceeded", "last_error": last_err},
                            is_error=True,
                        )
                    result.id = call.id
                    tool_results.append(result)

                new_msg = UserMessage(tool_results=tool_results)
            else:
                final_text = (
                    "# ⚠️ Step budget exhausted\n\n"
                    f"The agent used all {limit} steps without a final answer. "
                    "Consider raising AGENT_MAX_STEPS or splitting the task."
                )
    finally:
        await backend.close()
        log.log("run_completed", final_preview=(final_text or "")[:400])
        log.close()

    if dry_run:
        summary = "# 🧪 DRY RUN — proposed tool calls\n\n"
        if proposed_tool_calls:
            for i, p in enumerate(proposed_tool_calls, 1):
                summary += f"**{i}.** `{p['tool']}`\n\n```json\n"
                summary += json.dumps(p["arguments"], indent=2, ensure_ascii=False)
                summary += "\n```\n\n"
        else:
            summary += "_The agent did not propose any mutating tool calls._\n\n"
        summary += "---\n\n"
        summary += final_text or "_No final text was returned._"
        return summary

    return final_text


def _parse_args(argv: list[str]) -> argparse.Namespace:
    p = argparse.ArgumentParser(prog="speakers-crm-agent",
                                description="Autonomous agent for the PulseCore Speakers CRM.")
    p.add_argument("--task", help="Task text. If omitted, read from stdin.")
    p.add_argument("--dry-run", action="store_true",
                   help="Block mutating tool calls; show proposed calls only.")
    p.add_argument("--backend", choices=["anthropic", "openrouter"],
                   help="Override LLM_BACKEND for this run.")
    p.add_argument("--env", help="Load a specific .env file.")
    p.add_argument("--max-steps", type=int, help="Override AGENT_MAX_STEPS.")
    return p.parse_args(argv)


def main(argv: Optional[list[str]] = None) -> int:
    args = _parse_args(argv or sys.argv[1:])
    try:
        cfg = Config.load(env_file=args.env)
        if args.backend:
            cfg = Config(**{**cfg.__dict__, "backend": args.backend})  # type: ignore[misc]
        cfg.validate()
    except ConfigError as e:
        print(f"Configuration error: {e}", file=sys.stderr)
        return 2

    task = args.task or sys.stdin.read().strip()
    if not task:
        print("No task provided. Pass --task \"...\" or pipe text on stdin.", file=sys.stderr)
        return 2

    try:
        summary = asyncio.run(run_agent(
            cfg, task, dry_run=args.dry_run, max_steps=args.max_steps,
        ))
    except KeyboardInterrupt:
        print("\nInterrupted.", file=sys.stderr)
        return 130
    except Exception as e:
        # Redact the top-level exception message too — cheap defense-in-depth.
        safe = redact({"error": type(e).__name__, "message": str(e)})
        print(f"Agent crashed: {json.dumps(safe)}", file=sys.stderr)
        return 1

    print(summary)
    return 0


if __name__ == "__main__":
    sys.exit(main())
