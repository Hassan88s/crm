# Speakers CRM Agent — Build Prompt

Paste this prompt into any capable coding assistant (Claude Code, Cursor, etc.) to
rebuild or extend the agent. This is the exact specification that produced the
current implementation in [speakers_crm_agent/](speakers_crm_agent/).

---

Build a production-ready autonomous agent named "Speakers CRM Agent" that operates
on my Laravel CRM:

CRM URL: https://crm-speakers.com

The agent must use **Claude Opus 5** as its reasoning and tool-calling model.

## Non-negotiable model requirements

Do not use:

- OpenAI models
- The OpenAI API
- The OpenAI Python SDK
- Hermes-3
- Ollama or any local model
- XML-style Hermes tool calls

Provide two supported Claude Opus 5 runtime backends:

**A. Direct Anthropic API**

- Provider: Anthropic
- Default model: `claude-opus-5`
- API key environment variable: `ANTHROPIC_API_KEY`
- Model environment variable: `ANTHROPIC_MODEL`
- Default API base: `https://api.anthropic.com`
- Use Claude's native `tool_use` and `tool_result` content blocks.

**B. OpenRouter**

- Provider: OpenRouter
- Default model: `anthropic/claude-opus-5`
- API key environment variable: `OPENROUTER_API_KEY`
- Model environment variable: `OPENROUTER_MODEL`
- Default API base: `https://openrouter.ai/api/v1`
- Use OpenRouter's supported tool-calling format.
- Connect with `httpx`; do not install or use the OpenAI SDK.

Select the backend using `LLM_BACKEND=anthropic` or `LLM_BACKEND=openrouter`.

Create a common backend interface so the agent loop does not contain
provider-specific logic.

## CRM connection through MCP

The agent must connect to my CRM exclusively through the existing MCP server:
`/mcp-server/pulsecore_mcp.py`

Use the official Python MCP SDK and communicate with the MCP server over stdio.
The MCP server is the source of truth for all available CRM operations.

The agent must:

1. Launch and initialize the MCP server.
2. Call `tools/list` at runtime.
3. Load every tool exposed by the MCP server.
4. Read each tool's name, description, and JSON input schema.
5. Dynamically convert the discovered tools into the correct schema for the
   selected Claude backend.
6. Never maintain a hardcoded list of MCP tool names.
7. Never hardcode CRM resource endpoints in the agent.
8. Use only tools actually exposed by the MCP server.
9. Report a clear capability gap if a requested operation has no matching MCP tool.
10. Keep the MCP session open for the full agent run and close it cleanly afterward.

For the Anthropic backend, convert MCP tools into Claude's native format:

```json
{
  "name": "tool_name",
  "description": "Tool description",
  "input_schema": {}
}
```

For OpenRouter, convert the same MCP tools into its supported function-tool schema.

Normalize tool calls and tool results behind a shared Python interface so the main
loop behaves identically with both backends.

## Required capabilities

Through dynamically discovered MCP tools, the agent must be capable of performing:

### 1. Read CRM resources

Events, Speakers, Campaigns, Campaign recipients, Email logs, Email replies,
SMTP accounts, IMAP accounts, Inbox messages, Dashboard statistics, Campaign
statistics, Reply statistics, Delivery and failure statistics.

Support pagination and do not assume that a single response contains every record.

### 2. Manage events and speakers

CRUD for Events and Speakers. Before changing or deleting a record, retrieve it
and verify its ID and current state. After a mutation, read the affected record
again when a suitable tool is available and verify that the requested change
succeeded.

### 3. Manage communication accounts and API keys

CRUD for SMTP accounts, IMAP accounts, CRM API keys.

Never expose credentials, API keys, SMTP passwords, IMAP passwords, authorization
headers, or secret values in console output, JSONL logs, exceptions, Markdown
summaries, or debug output. Redact all secrets consistently.

### 4. Drive campaigns

Start, pause, resume, resend one failed recipient, bulk-resend all eligible
failed recipients, enable/disable PDF attachment, delete a campaign, inspect
campaign status, inspect recipient delivery status, verify campaign state after
an operation.

**Bulk retry is critical.** SendGrid throttled a campaign containing ~99,000
recipients. The agent must be able to identify eligible failed recipients and
invoke the MCP server's bulk-resend capability.

For resend operations:

1. Inspect the campaign first.
2. Confirm the target campaign ID.
3. Obtain failed-recipient counts and statuses.
4. Use the MCP server's supported resend tool.
5. Do not invent direct SendGrid requests.
6. Do not resend successful or currently processing recipients.
7. Do not resend unsubscribed, suppressed, spam-complaint, permanently bounced,
   or otherwise ineligible recipients if those statuses are available.
8. Respect server-side throttling, batching, cooldowns, and rate limits.
9. Handle HTTP 429 or temporary MCP errors with bounded exponential backoff.
10. Prevent accidental duplicate retries of the same recipient during one run.
11. Report attempted, accepted, skipped, and failed retry counts.
12. Verify the campaign or recipient state after the resend request whenever
    possible.

### 5. Manage email replies

List, filter, search, fetch new messages from IMAP, reclassify, change a reply's
category, send a reply through the appropriate CRM tool, delete a reply, report
reply-category statistics.

Supported categories: Interested, Not Interested, Info Request, Out of Office,
Spam, Negative, No Reply, Bounced, Confirmed, Manual Review.

Preserve the exact controlled category values expected by the CRM. Do not invent
a new category unless the MCP tool schema explicitly permits it.

Before sending a response, verify: recipient, associated speaker, associated
event or campaign, reply category, relevant inbound message, selected sending
account, whether the address is bounced / suppressed / unsubscribed.

### 6. Enrich and verify speakers

Use the MCP tools corresponding to `speakers.verify` and `speakers.find_linkedin`.
Find speakers with missing LinkedIn URLs, search for likely LinkedIn profiles,
validate company data and job titles, detect conflicts between existing CRM data
and enrichment results, update only supported fields through MCP tools, report
uncertain matches instead of silently accepting them.

For LinkedIn matching, compare available evidence: full name, company, job title,
country, event, existing biography, existing website or social links. Do not
treat a weak name-only match as verified.

### 7. Scrape and import

Use the MCP tools corresponding to `scraper.scrape`, `scraper.discover`,
`scraper.import`. Scrape a provided URL, discover relevant events, inspect
discovered data, import approved results through the MCP server, report
duplicates / incomplete records / validation failures / import counts.

Never create a second custom scraper or bypass the MCP scraper tools unless
explicitly instructed.

### 8. Analyze and enhance CRM performance

Aggregate campaign results, calculate delivery / failure / bounce / reply / positive
reply rates, compare performance by campaign / SMTP account / event / over time,
identify underperforming campaigns, identify stale speakers with no reply, detect
campaigns affected by throttling, detect unusual spikes in bounces or failures,
propose next-best actions, produce clear Markdown reports.

Clearly label the difference between Facts / Metrics / Inferences /
Recommendations. Never invent missing statistics.

## Agent execution loop

CLI accepts a task from either:

```bash
python -m speakers_crm_agent.agent --task "task text"
```

or standard input:

```bash
echo "task text" | python -m speakers_crm_agent.agent
```

The main loop must: load and validate configuration; read the task; launch the
MCP server over stdio; initialize the MCP session; discover all available MCP
tools; convert the tools for the selected Claude backend; send the system prompt,
user task, and discovered tools to Claude Opus 5; receive Claude's response;
detect native tool calls; validate tool arguments against the MCP JSON schema;
execute requested MCP tools; capture and normalize tool results; return tool
results to Claude using the provider's native format; continue reasoning and
tool execution until the task is complete; stop safely on repeated failures,
invalid tool calls, or the configured maximum number of steps; emit a final
Markdown summary to stdout; close the MCP session and subprocess cleanly.

The loop must support multiple tool calls in one model response. Do not use
regex-only parsing for tool calls — use structured native tool-call objects from
the provider response.

## Mutation and safety rules

The agent may perform mutations only when the natural-language task clearly
requests them. A read-only task must never trigger a write, send, resend,
delete, import, or account-management operation.

For destructive or high-impact operations (deleting records, deleting campaigns,
deleting accounts, deleting API keys, sending replies, starting large campaigns,
bulk-resending campaign recipients) the agent must first inspect the target and
confirm that the tool arguments match the user's request.

If the task is ambiguous about the target record or campaign, stop and request
clarification rather than guessing.

Never repeat a successful mutating tool call merely because the model response
timed out afterward. Use idempotency protection where supported.

## Logging

Log every agent run to `runs/{UTC_TIMESTAMP}.jsonl`. Each JSONL record should
include, where applicable: UTC timestamp, run ID, agent step number, record
type, backend, model, MCP tool name, redacted tool arguments, redacted tool
response, duration, success or failure status, error type, retry count.

Record types: `run_started`, `mcp_initialized`, `tools_discovered`,
`model_request`, `model_response`, `tool_call`, `tool_result`, `retry`,
`run_completed`, `run_failed`.

Do not store API keys, authorization headers, passwords, complete environment
variables, provider credentials. Logging must remain valid even if a tool result
is not JSON serializable.

## Authentication

Read the CRM API key from `PULSECORE_API_KEY`. Generated at
`https://crm-speakers.com/admin/settings#api-keys`.

Pass CRM configuration into the MCP subprocess environment:

```env
PULSECORE_API_BASE=https://crm-speakers.com/api/v1
PULSECORE_API_KEY=...
```

The MCP server must send `Authorization: Bearer <PULSECORE_API_KEY>` on every
CRM API request. The main agent must not bypass the MCP server to call CRM
endpoints directly. Never print or log the authorization header.

## Configuration file

Create `.env.example` containing:

```env
# LLM backend: anthropic or openrouter
LLM_BACKEND=anthropic
# Direct Anthropic backend
ANTHROPIC_API_KEY=
ANTHROPIC_MODEL=claude-opus-5
ANTHROPIC_BASE_URL=https://api.anthropic.com
# OpenRouter backend
OPENROUTER_API_KEY=
OPENROUTER_MODEL=anthropic/claude-opus-5
OPENROUTER_BASE_URL=https://openrouter.ai/api/v1
OPENROUTER_APP_NAME=Speakers CRM Agent
OPENROUTER_SITE_URL=https://crm-speakers.com
# PulseCore Speakers CRM
PULSECORE_API_BASE=https://crm-speakers.com/api/v1
PULSECORE_API_KEY=
# MCP server
MCP_SERVER_CMD=python mcp-server/pulsecore_mcp.py
# Agent controls
AGENT_MAX_STEPS=30
HTTP_TIMEOUT_SECONDS=120
MAX_TOOL_RETRIES=3
LOG_LEVEL=INFO
```

Validate required configuration at startup and provide a clear error without
displaying secret values.

## Required project structure

```
speakers_crm_agent/
├── __init__.py
├── agent.py
├── config.py
├── mcp_client.py
├── prompts.py
├── logging_utils.py
└── backends/
    ├── __init__.py
    ├── base.py
    ├── anthropic.py
    └── openrouter.py
runs/
└── .gitkeep
tests/
├── test_config.py
├── test_tool_conversion.py
├── test_tool_call_loop.py
├── test_redaction.py
└── test_dry_run.py
.env.example
.gitignore
README.md
pyproject.toml
```

## Dependencies (`pyproject.toml`)

```
mcp>=1.2.0,<2
anthropic
httpx
python-dotenv
pydantic
rich
pytest
pytest-asyncio
```

Do not add `openai` as a dependency.

## Dry-run

Add `--dry-run`. In dry-run mode the agent must:

- Start the MCP server
- Discover the real MCP tools
- Allow Claude Opus 5 to select the appropriate tools
- Validate the proposed arguments
- Print the proposed tool calls
- Not execute business mutating MCP tools
- Never modify CRM data
- Never send or resend an email
- Never import or delete records
- Clearly label the output as DRY RUN

## Definition of done

The project is complete only when:

1. Both Claude Opus 5 backends are implemented.
2. No OpenAI API, model, package, or SDK is used.
3. The MCP server is initialized successfully.
4. MCP tools are discovered dynamically.
5. No CRM business endpoint is hardcoded into the agent.
6. Native structured tool calling works end to end.
7. The agent can complete multi-step read-only and mutating tasks.
8. JSONL logging works with secret redaction.
9. Dry-run mode prints valid proposed tool calls without executing them.
10. Automated tests pass.
11. The README commands match the actual implementation.
12. The example dry-run is executed and its output is shown.
