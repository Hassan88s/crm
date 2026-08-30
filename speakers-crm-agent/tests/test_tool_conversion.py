"""Tool-schema conversion: MCP → Anthropic native, and MCP → OpenRouter function schema."""
import pytest

from speakers_crm_agent.backends.base import NormalizedTool

pytest.importorskip("anthropic")


@pytest.fixture
def sample_tool() -> NormalizedTool:
    return NormalizedTool(
        name="resend_failed_recipients",
        description="Re-queue every failed recipient on a campaign.",
        input_schema={
            "type": "object",
            "properties": {"campaign_id": {"type": "integer"}},
            "required": ["campaign_id"],
        },
    )


def test_anthropic_tool_conversion_matches_claude_schema(sample_tool):
    from speakers_crm_agent.backends.anthropic import AnthropicBackend
    b = AnthropicBackend(api_key="sk-ant-test")
    out = b._tools_for_provider([sample_tool])
    assert out == [{
        "name": "resend_failed_recipients",
        "description": "Re-queue every failed recipient on a campaign.",
        "input_schema": {
            "type": "object",
            "properties": {"campaign_id": {"type": "integer"}},
            "required": ["campaign_id"],
        },
    }]


def test_openrouter_tool_conversion_matches_openai_function_schema(sample_tool):
    from speakers_crm_agent.backends.openrouter import OpenRouterBackend
    b = OpenRouterBackend(api_key="sk-or-test")
    out = b._tools_for_provider([sample_tool])
    assert out == [{
        "type": "function",
        "function": {
            "name": "resend_failed_recipients",
            "description": "Re-queue every failed recipient on a campaign.",
            "parameters": {
                "type": "object",
                "properties": {"campaign_id": {"type": "integer"}},
                "required": ["campaign_id"],
            },
        },
    }]


def test_empty_schema_becomes_valid_object(sample_tool):
    from speakers_crm_agent.backends.anthropic import AnthropicBackend
    b = AnthropicBackend(api_key="sk-ant-test")
    empty = NormalizedTool(name="stats", description="", input_schema={})
    out = b._tools_for_provider([empty])
    assert out[0]["input_schema"] == {"type": "object", "properties": {}}
