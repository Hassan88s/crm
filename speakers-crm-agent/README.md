# Speakers CRM Agent

Autonomous agent for the **PulseCore Speakers CRM** (https://crm-speakers.com) driven
by **Claude Opus 5** and connected to the CRM exclusively through the MCP server at
[`../mcp-server/pulsecore_mcp.py`](../mcp-server/pulsecore_mcp.py).

- **No OpenAI SDK, no OpenAI models, no OpenAI API.**
- **No local models, no Ollama, no Hermes.**
- Only Claude Opus 5 — served either directly by Anthropic or via OpenRouter.

## Architecture

```
┌────────────────────┐        stdio       ┌──────────────────────┐    HTTPS    ┌────────────────┐
│ speakers_crm_agent │ ◀────────────────▶ │ mcp-server/          │ ──────────▶ │ crm-speakers   │
│   (Claude Opus 5)  │   list_tools /     │ pulsecore_mcp.py     │   Bearer    │   .com /api/v1 │
│                    │   call_tool        │  (~50 CRM tools)     │             │                │
└────────────────────┘                    └──────────────────────┘             └────────────────┘
```

The agent:
1. Launches the MCP server as a subprocess over stdio.
2. Calls `tools/list` to discover every operation the CRM exposes.
3. Converts the discovered tools into Claude's native `tool_use` schema (Anthropic
   backend) or OpenRouter's OpenAI-compatible function schema.
4. Sends the task + tools + system prompt to Claude Opus 5.
5. Executes tool calls, feeds results back as `tool_result` blocks.
6. Repeats until the model returns a final Markdown summary.

No CRM endpoint is hardcoded anywhere in the agent — all capabilities come from
MCP discovery.

## Requirements

- Python 3.10+
- The MCP server at `../mcp-server/pulsecore_mcp.py` (already in this repo)
- One of: Anthropic API key **or** OpenRouter API key
- A PulseCore CRM API key from https://crm-speakers.com/admin/settings#api-keys

## Installation

```bash
cd speakers-crm-agent
python -m venv .venv
# Windows
.venv\Scripts\activate
# macOS/Linux
source .venv/bin/activate

pip install -e ".[test]"
```

## Environment setup

```bash
cp .env.example .env
# edit .env and fill in the values
```

### Anthropic backend

```env
LLM_BACKEND=anthropic
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_MODEL=claude-opus-5
PULSECORE_API_KEY=pk_...
```

### OpenRouter backend

```env
LLM_BACKEND=openrouter
OPENROUTER_API_KEY=sk-or-...
OPENROUTER_MODEL=anthropic/claude-opus-5
PULSECORE_API_KEY=pk_...
```

## MCP server setup

The MCP server ships in this repo at `../mcp-server/`. Install its deps once:

```bash
pip install -r ../mcp-server/requirements.txt
```

The agent launches it automatically as a subprocess — you don't run it separately.

## CLI usage

```bash
# Task via flag
python -m speakers_crm_agent.agent --task "list all confirmed events"

# Task via stdin
echo "resend failed on campaign 12" | python -m speakers_crm_agent.agent

# Override backend for one run
python -m speakers_crm_agent.agent --backend openrouter --task "stats"

# Raise the step budget
python -m speakers_crm_agent.agent --max-steps 60 --task "enrich every speaker at event 5"

# Use a specific .env file
python -m speakers_crm_agent.agent --env prod.env --task "..."
```

Exit codes: `0` success · `1` runtime failure · `2` config or usage error · `130` interrupted.

## Dry-run mode

```bash
python -m speakers_crm_agent.agent \
  --task "List the 5 most-recent failed campaign recipients" \
  --dry-run
```

In dry-run mode:
- Read-only tools (`list_*`, `get_*`, `stats`, `whoami`, `discover_*`) execute normally.
- Mutating tools (create / update / delete / resend / send / import / toggle / etc.)
  are **blocked**. The agent still sees the proposed call and can reason about it, but
  no CRM data is changed.
- The output is prefixed `# 🧪 DRY RUN — proposed tool calls` and lists each blocked
  call in JSON.

### Example dry-run output

For task `"List the 5 most-recent failed campaign recipients"`, the agent proposes
a discovered tool such as:

```json
{
  "tool": "list_email_logs",
  "arguments": {
    "status": "failed",
    "per_page": 5
  }
}
```

The exact tool name and argument names come from MCP discovery at runtime — if the
server later renames or restructures a tool, the agent adapts automatically.

## Logging

Every run writes one file to `runs/{UTC_TIMESTAMP}_{run_id}.jsonl`. Record types:

`run_started`, `mcp_initialized`, `tools_discovered`, `model_request`,
`model_response`, `tool_call`, `tool_result`, `retry`, `run_completed`, `run_failed`

Each line includes: `ts`, `run_id`, `step`, `type`, `duration_ms`, `success`,
`error`, plus record-specific fields. **All values are recursively scrubbed of
API keys, bearer headers, passwords, and provider tokens** before they hit disk.

## Safety behavior

| Rule | Enforced by |
|---|---|
| No writes on read-only tasks | Claude Opus 5 (via system prompt) |
| Read-before-write on mutations | Claude Opus 5 (via system prompt) |
| Mutating tools blocked in `--dry-run` | Agent harness (name-prefix heuristic) |
| MCP argument validation before call | `MCPClient.validate_arguments` |
| Bounded retries with exponential backoff | Agent loop (max = `MAX_TOOL_RETRIES`) |
| Step budget | `AGENT_MAX_STEPS` |
| Secret redaction in logs | `logging_utils.redact` |
| LLM keys stripped from MCP subprocess env | `Config.mcp_subprocess_env` |

## Sample tasks

```bash
python -m speakers_crm_agent.agent \
  --task "Resend all eligible failed recipients on campaign 12"

python -m speakers_crm_agent.agent \
  --task "Find LinkedIn URLs for every speaker missing one at event 5"

python -m speakers_crm_agent.agent \
  --task "Report reply rate per SMTP account for the last 14 days"

python -m speakers_crm_agent.agent \
  --task "Show underperforming campaigns and recommend the next best action"

python -m speakers_crm_agent.agent \
  --task "Fetch new IMAP replies and list every message requiring manual review"
```

## Troubleshooting

**`Configuration error: Missing required environment variables: ...`**
The listed variables aren't set. Copy `.env.example` to `.env` and fill them in.

**`BackendError: Anthropic request failed: ... 401`**
`ANTHROPIC_API_KEY` is wrong or revoked.

**`BackendError: OpenRouter HTTP 402`**
OpenRouter credits are exhausted.

**`unauthorized` from MCP tool calls**
`PULSECORE_API_KEY` is missing, wrong, or revoked. Generate a new one at
https://crm-speakers.com/admin/settings#api-keys.

**MCP subprocess fails to start**
Check `MCP_SERVER_CMD` — the path must be relative to where you launch the agent,
or absolute.

## Running the tests

```bash
pytest -q
```

Tests use mocked backends and a fake MCP client — nothing hits the real Anthropic,
OpenRouter, or CRM services.
