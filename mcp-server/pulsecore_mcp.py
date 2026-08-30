"""
PulseCore CRM — MCP Server
==========================

Exposes the crm-speakers.com REST API (/api/v1/*) as MCP tools so any
MCP-capable agent (Hermes, Claude, GPT, etc.) can drive the CRM without
knowing HTTP.

Run:
    export PULSECORE_API_KEY=pk_xxxxx
    python pulsecore_mcp.py

Register with an MCP client (e.g. Claude Desktop) via:
    {
      "mcpServers": {
        "pulsecore": {
          "command": "python",
          "args": ["/path/to/pulsecore_mcp.py"],
          "env": { "PULSECORE_API_KEY": "pk_xxxxx" }
        }
      }
    }
"""

from __future__ import annotations

import os
import sys
import json
from typing import Any, Optional

import httpx
from mcp.server.fastmcp import FastMCP

# ── Config ────────────────────────────────────────────────────────────────

API_BASE = os.environ.get("PULSECORE_API_BASE", "https://crm-speakers.com/api/v1").rstrip("/")
API_KEY = os.environ.get("PULSECORE_API_KEY", "")

if not API_KEY:
    print("ERROR: PULSECORE_API_KEY env var is required.", file=sys.stderr)
    sys.exit(1)

client = httpx.Client(
    base_url=API_BASE,
    headers={
        "Authorization": f"Bearer {API_KEY}",
        "Accept": "application/json",
        "Content-Type": "application/json",
    },
    timeout=60.0,
)

mcp = FastMCP("pulsecore-crm")


def _req(method: str, path: str, *, params: dict | None = None, json_body: dict | None = None) -> Any:
    """Thin HTTP wrapper that raises MCP-friendly errors."""
    try:
        r = client.request(method, path, params=params, json=json_body)
    except httpx.HTTPError as e:
        return {"error": "http_error", "message": str(e)}
    try:
        payload = r.json()
    except json.JSONDecodeError:
        payload = {"raw": r.text}
    if r.status_code >= 400:
        return {"error": f"http_{r.status_code}", "response": payload}
    return payload


# ── Meta ──────────────────────────────────────────────────────────────────

@mcp.tool()
def whoami() -> dict:
    """Return details about the currently authenticated API key."""
    return _req("GET", "/me")


@mcp.tool()
def stats() -> dict:
    """Global counts: events, speakers, campaigns, sent/failed emails,
    replies by category, recipients by status. Use this to understand
    the current state of the CRM before planning any bulk action."""
    return _req("GET", "/stats")


# ── Events ────────────────────────────────────────────────────────────────

@mcp.tool()
def list_events(status: Optional[str] = None, search: Optional[str] = None,
                page: int = 1, per_page: int = 25) -> dict:
    """List events. status: draft|planning|confirmed. Paginated."""
    return _req("GET", "/events", params={"status": status, "search": search,
                                          "page": page, "per_page": per_page})


@mcp.tool()
def get_event(event_id: int) -> dict:
    """Get a single event with speaker count."""
    return _req("GET", f"/events/{event_id}")


@mcp.tool()
def create_event(name: str, location: Optional[str] = None, date: Optional[str] = None,
                 end_date: Optional[str] = None, description: Optional[str] = None,
                 status: str = "draft") -> dict:
    """Create an event. Dates as YYYY-MM-DD. status: draft|planning|confirmed."""
    return _req("POST", "/events", json_body={
        "name": name, "location": location, "date": date,
        "end_date": end_date, "description": description, "status": status,
    })


@mcp.tool()
def update_event(event_id: int, **fields) -> dict:
    """Update any subset of fields on an event."""
    return _req("PATCH", f"/events/{event_id}", json_body=fields)


@mcp.tool()
def delete_event(event_id: int) -> dict:
    """Permanently delete an event."""
    return _req("DELETE", f"/events/{event_id}")


@mcp.tool()
def list_event_speakers(event_id: int, page: int = 1, per_page: int = 50) -> dict:
    """List every speaker attached to an event."""
    return _req("GET", f"/events/{event_id}/speakers",
                params={"page": page, "per_page": per_page})


# ── Speakers ──────────────────────────────────────────────────────────────

@mcp.tool()
def list_speakers(event_id: Optional[int] = None, country: Optional[str] = None,
                  seniority: Optional[str] = None, search: Optional[str] = None,
                  page: int = 1, per_page: int = 50) -> dict:
    """List speakers with filters. search matches name, email, company."""
    return _req("GET", "/speakers", params={
        "event_id": event_id, "country": country, "seniority": seniority,
        "search": search, "page": page, "per_page": per_page,
    })


@mcp.tool()
def get_speaker(speaker_id: int) -> dict:
    """Get a single speaker with their event."""
    return _req("GET", f"/speakers/{speaker_id}")


@mcp.tool()
def create_speaker(first_name: str, last_name: str, email: Optional[str] = None,
                   title: Optional[str] = None, company: Optional[str] = None,
                   linkedin_url: Optional[str] = None, seniority: Optional[str] = None,
                   country: Optional[str] = None, event_id: Optional[int] = None) -> dict:
    """Create a speaker record."""
    return _req("POST", "/speakers", json_body={
        "first_name": first_name, "last_name": last_name, "email": email,
        "title": title, "company": company, "linkedin_url": linkedin_url,
        "seniority": seniority, "country": country, "event_id": event_id,
    })


@mcp.tool()
def update_speaker(speaker_id: int, **fields) -> dict:
    """Update any subset of a speaker's fields."""
    return _req("PATCH", f"/speakers/{speaker_id}", json_body=fields)


@mcp.tool()
def delete_speaker(speaker_id: int) -> dict:
    """Permanently delete a speaker."""
    return _req("DELETE", f"/speakers/{speaker_id}")


@mcp.tool()
def verify_speaker_profile(speaker_id: int) -> dict:
    """Run the CRM's AI profile verifier on a speaker (fills in missing
    title/company/seniority/country from public web knowledge)."""
    return _req("POST", f"/speakers/{speaker_id}/verify")


@mcp.tool()
def find_speaker_linkedin(speaker_id: int) -> dict:
    """Find and store a speaker's LinkedIn URL (Apollo → OpenAI fallback)."""
    return _req("POST", f"/speakers/{speaker_id}/find-linkedin")


# ── Campaigns ─────────────────────────────────────────────────────────────

@mcp.tool()
def list_campaigns(status: Optional[str] = None, page: int = 1, per_page: int = 25) -> dict:
    """List campaigns. status: draft|running|paused|completed|failed."""
    return _req("GET", "/campaigns", params={"status": status,
                                             "page": page, "per_page": per_page})


@mcp.tool()
def get_campaign(campaign_id: int) -> dict:
    """Get a campaign with recipient counts by status and progress percent."""
    return _req("GET", f"/campaigns/{campaign_id}")


@mcp.tool()
def list_campaign_recipients(campaign_id: int, status: Optional[str] = None,
                             page: int = 1, per_page: int = 50) -> dict:
    """List recipients on a campaign. status: pending|processing|sent|failed|skipped."""
    return _req("GET", f"/campaigns/{campaign_id}/recipients",
                params={"status": status, "page": page, "per_page": per_page})


@mcp.tool()
def start_campaign(campaign_id: int) -> dict:
    """Start (or resume) a campaign so the cron will process it."""
    return _req("POST", f"/campaigns/{campaign_id}/start")


@mcp.tool()
def pause_campaign(campaign_id: int) -> dict:
    """Pause a running campaign."""
    return _req("POST", f"/campaigns/{campaign_id}/pause")


@mcp.tool()
def resume_campaign(campaign_id: int) -> dict:
    """Resume a paused campaign."""
    return _req("POST", f"/campaigns/{campaign_id}/resume")


@mcp.tool()
def resend_failed_recipients(campaign_id: int) -> dict:
    """Re-queue EVERY failed recipient on a campaign. They will be spaced
    by the campaign's throttle_seconds. Use this after SendGrid throttling
    or a temporary provider outage."""
    return _req("POST", f"/campaigns/{campaign_id}/resend-failed")


@mcp.tool()
def resend_one_recipient(campaign_id: int, recipient_id: int) -> dict:
    """Re-queue a single failed recipient on a campaign."""
    return _req("POST", f"/campaigns/{campaign_id}/recipients/{recipient_id}/resend")


@mcp.tool()
def toggle_campaign_attach(campaign_id: int) -> dict:
    """Toggle whether the campaign's PDF agenda is attached to each email."""
    return _req("POST", f"/campaigns/{campaign_id}/toggle-attach")


@mcp.tool()
def delete_campaign(campaign_id: int) -> dict:
    """Delete a campaign and all its recipients."""
    return _req("DELETE", f"/campaigns/{campaign_id}")


# ── Email logs ────────────────────────────────────────────────────────────

@mcp.tool()
def list_email_logs(status: Optional[str] = None, speaker_id: Optional[int] = None,
                    smtp_account_id: Optional[int] = None, from_date: Optional[str] = None,
                    to_date: Optional[str] = None, page: int = 1, per_page: int = 50) -> dict:
    """List raw email send logs. status: sent|failed. Dates: ISO strings."""
    return _req("GET", "/email-logs", params={
        "status": status, "speaker_id": speaker_id, "smtp_account_id": smtp_account_id,
        "from": from_date, "to": to_date, "page": page, "per_page": per_page,
    })


@mcp.tool()
def get_email_log(log_id: int) -> dict:
    """Get one email log entry (full body + error if any)."""
    return _req("GET", f"/email-logs/{log_id}")


# ── Replies ───────────────────────────────────────────────────────────────

REPLY_CATEGORIES = ("Interested", "Not Interested", "Info Request", "Out of Office",
                    "Spam", "Negative", "No Reply", "Bounced", "Confirmed", "Manual Review")


@mcp.tool()
def list_replies(category: Optional[str] = None, speaker_id: Optional[int] = None,
                 search: Optional[str] = None, page: int = 1, per_page: int = 50) -> dict:
    """List classified email replies. Valid categories: Interested, Not Interested,
    Info Request, Out of Office, Spam, Negative, No Reply, Bounced, Confirmed,
    Manual Review."""
    return _req("GET", "/replies", params={
        "category": category, "speaker_id": speaker_id, "search": search,
        "page": page, "per_page": per_page,
    })


@mcp.tool()
def get_reply(reply_id: int) -> dict:
    """Get a single reply with its speaker."""
    return _req("GET", f"/replies/{reply_id}")


@mcp.tool()
def fetch_new_replies() -> dict:
    """Trigger IMAP fetch + AI classification for new replies."""
    return _req("POST", "/replies/fetch")


@mcp.tool()
def change_reply_category(reply_id: int, category: str) -> dict:
    """Manually recategorize a reply. Category must be one of the 10 valid values."""
    return _req("POST", f"/replies/{reply_id}/category", json_body={"category": category})


@mcp.tool()
def reclassify_reply(reply_id: int) -> dict:
    """Re-run AI classification on a reply."""
    return _req("POST", f"/replies/{reply_id}/reclassify")


@mcp.tool()
def send_reply(reply_id: int, subject: Optional[str] = None,
               body: Optional[str] = None) -> dict:
    """Send an outbound reply to a received email."""
    return _req("POST", f"/replies/{reply_id}/send-reply",
                json_body={"subject": subject, "body": body})


@mcp.tool()
def delete_reply(reply_id: int) -> dict:
    """Delete a reply row."""
    return _req("DELETE", f"/replies/{reply_id}")


# ── SMTP accounts ─────────────────────────────────────────────────────────

@mcp.tool()
def list_smtp_accounts(active: Optional[bool] = None) -> dict:
    """List SMTP accounts. Pass active=True to only get active ones."""
    return _req("GET", "/smtp-accounts", params={"active": active})


@mcp.tool()
def get_smtp_account(account_id: int) -> dict:
    return _req("GET", f"/smtp-accounts/{account_id}")


@mcp.tool()
def create_smtp_account(name: str, host: str, port: int, username: str,
                        password: str, encryption: str = "tls",
                        from_address: Optional[str] = None, from_name: Optional[str] = None,
                        is_active: bool = True) -> dict:
    """Add an SMTP account. encryption: tls|ssl|none."""
    return _req("POST", "/smtp-accounts", json_body={
        "name": name, "host": host, "port": port, "username": username,
        "password": password, "encryption": encryption,
        "from_address": from_address, "from_name": from_name, "is_active": is_active,
    })


@mcp.tool()
def update_smtp_account(account_id: int, **fields) -> dict:
    return _req("PATCH", f"/smtp-accounts/{account_id}", json_body=fields)


@mcp.tool()
def delete_smtp_account(account_id: int) -> dict:
    return _req("DELETE", f"/smtp-accounts/{account_id}")


@mcp.tool()
def toggle_smtp_account(account_id: int) -> dict:
    """Toggle an SMTP account's is_active flag."""
    return _req("POST", f"/smtp-accounts/{account_id}/toggle")


# ── IMAP accounts ─────────────────────────────────────────────────────────

@mcp.tool()
def list_imap_accounts(active: Optional[bool] = None) -> dict:
    return _req("GET", "/imap-accounts", params={"active": active})


@mcp.tool()
def get_imap_account(account_id: int) -> dict:
    return _req("GET", f"/imap-accounts/{account_id}")


@mcp.tool()
def create_imap_account(name: str, host: str, port: int, username: str,
                        password: str, encryption: str = "ssl",
                        color: Optional[str] = None, is_active: bool = True) -> dict:
    """Add an IMAP account. encryption: ssl|tls|starttls|none."""
    return _req("POST", "/imap-accounts", json_body={
        "name": name, "host": host, "port": port, "username": username,
        "password": password, "encryption": encryption,
        "color": color, "is_active": is_active,
    })


@mcp.tool()
def update_imap_account(account_id: int, **fields) -> dict:
    return _req("PATCH", f"/imap-accounts/{account_id}", json_body=fields)


@mcp.tool()
def delete_imap_account(account_id: int) -> dict:
    return _req("DELETE", f"/imap-accounts/{account_id}")


@mcp.tool()
def toggle_imap_account(account_id: int) -> dict:
    return _req("POST", f"/imap-accounts/{account_id}/toggle")


# ── Inbox (live IMAP) ─────────────────────────────────────────────────────

@mcp.tool()
def list_inbox(folder: str = "INBOX", account_id: Optional[int] = None,
               limit: int = 50) -> dict:
    """List messages in an IMAP folder (live, not from DB)."""
    return _req("GET", "/inbox", params={
        "folder": folder, "account_id": account_id, "limit": limit,
    })


@mcp.tool()
def get_inbox_message(uid: str, folder: str = "INBOX",
                      account_id: Optional[int] = None) -> dict:
    """Fetch one IMAP message by UID."""
    return _req("GET", f"/inbox/{uid}", params={
        "folder": folder, "account_id": account_id,
    })


@mcp.tool()
def mark_inbox_read(uid: str, folder: str = "INBOX",
                    account_id: Optional[int] = None) -> dict:
    return _req("POST", f"/inbox/{uid}/read", json_body={
        "folder": folder, "account_id": account_id,
    })


@mcp.tool()
def move_inbox_message(uid: str, to_folder: str, folder: str = "INBOX",
                       account_id: Optional[int] = None) -> dict:
    return _req("POST", f"/inbox/{uid}/move", json_body={
        "folder": folder, "to_folder": to_folder, "account_id": account_id,
    })


@mcp.tool()
def delete_inbox_message(uid: str, folder: str = "INBOX",
                         account_id: Optional[int] = None) -> dict:
    return _req("DELETE", f"/inbox/{uid}", json_body={
        "folder": folder, "account_id": account_id,
    })


# ── Scraper ───────────────────────────────────────────────────────────────

@mcp.tool()
def scrape_url(url: str) -> dict:
    """Scrape a single event URL. Returns extracted speakers + event metadata."""
    return _req("POST", "/scraper/scrape", json_body={"url": url})


@mcp.tool()
def discover_events(topic: str, region: Optional[str] = None, limit: int = 10) -> dict:
    """Use AI to discover event URLs for a topic/region."""
    return _req("POST", "/scraper/discover", json_body={
        "topic": topic, "region": region, "limit": limit,
    })


@mcp.tool()
def import_scraped(event_data: dict, speakers: list) -> dict:
    """Import scraped speakers into the CRM under a new or existing event."""
    return _req("POST", "/scraper/import", json_body={
        "event": event_data, "speakers": speakers,
    })


# ── Entrypoint ────────────────────────────────────────────────────────────

if __name__ == "__main__":
    mcp.run()
