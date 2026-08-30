# PulseCore CRM — MCP Server

Exposes the CRM (crm-speakers.com) as MCP tools so agents can operate on it.

## Install

```bash
cd mcp-server
python -m venv .venv
source .venv/bin/activate      # Windows: .venv\Scripts\activate
pip install -r requirements.txt
```

## Configure

Generate an API key at https://crm-speakers.com/admin/settings#api-keys, then:

```bash
export PULSECORE_API_KEY=pk_xxxxxxxxxxxxxxxx
export PULSECORE_API_BASE=https://crm-speakers.com/api/v1   # optional
```

## Run standalone

```bash
python pulsecore_mcp.py
```

## Wire into a client

### Claude Desktop (`claude_desktop_config.json`)

```json
{
  "mcpServers": {
    "pulsecore": {
      "command": "python",
      "args": ["/absolute/path/to/mcp-server/pulsecore_mcp.py"],
      "env": {
        "PULSECORE_API_KEY": "pk_xxxxxxxxxxxxxxxx"
      }
    }
  }
}
```

### Hermes agent

Point your MCP client library at `python pulsecore_mcp.py` — every `@mcp.tool()`
becomes a callable function. Hermes-3 (via Ollama or OpenRouter) will get the
tool schemas automatically.

## Tools exposed

**Meta:** `whoami`, `stats`

**Events:** `list_events`, `get_event`, `create_event`, `update_event`,
`delete_event`, `list_event_speakers`

**Speakers:** `list_speakers`, `get_speaker`, `create_speaker`, `update_speaker`,
`delete_speaker`, `verify_speaker_profile`, `find_speaker_linkedin`

**Campaigns:** `list_campaigns`, `get_campaign`, `list_campaign_recipients`,
`start_campaign`, `pause_campaign`, `resume_campaign`,
**`resend_failed_recipients`** (the SendGrid recovery one), `resend_one_recipient`,
`toggle_campaign_attach`, `delete_campaign`

**Email logs:** `list_email_logs`, `get_email_log`

**Replies:** `list_replies`, `get_reply`, `fetch_new_replies`, `change_reply_category`,
`reclassify_reply`, `send_reply`, `delete_reply`

**SMTP accounts:** `list_smtp_accounts`, `get_smtp_account`, `create_smtp_account`,
`update_smtp_account`, `delete_smtp_account`, `toggle_smtp_account`

**IMAP accounts:** same shape as SMTP.

**Inbox:** `list_inbox`, `get_inbox_message`, `mark_inbox_read`,
`move_inbox_message`, `delete_inbox_message`

**Scraper:** `scrape_url`, `discover_events`, `import_scraped`
