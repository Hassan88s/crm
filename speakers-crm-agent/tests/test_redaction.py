"""Recursive secret redaction — the last line of defense before logs hit disk."""
import json

from speakers_crm_agent.logging_utils import REDACTED, redact


def test_redacts_by_key_name():
    out = redact({"api_key": "value", "note": "keep"})
    assert out["api_key"] == REDACTED
    assert out["note"] == "keep"


def test_redacts_authorization_header():
    out = redact({"headers": {"Authorization": "Bearer sk-ant-abcdef1234567890"}})
    assert out["headers"]["Authorization"] == REDACTED


def test_redacts_by_value_shape():
    # Key name is innocuous, but the value shape reveals a secret.
    out = redact({"note": "sk-ant-abcdef1234567890"})
    assert out["note"] == REDACTED


def test_redacts_pulsecore_token_shape():
    out = redact({"anything": "pk_ABC123DEF456GHI789JKL"})
    assert out["anything"] == REDACTED


def test_deeply_nested():
    payload = {
        "env": {"PULSECORE_API_KEY": "pk_secretsecretxxxx"},
        "list": [{"password": "hunter2"}, "plain"],
    }
    out = redact(payload)
    assert out["env"]["PULSECORE_API_KEY"] == REDACTED
    assert out["list"][0]["password"] == REDACTED
    assert out["list"][1] == "plain"


def test_redacted_output_is_json_serializable():
    out = redact({"pw": "x", "nested": {"api_key": "y", "z": [1, 2]}})
    json.dumps(out)  # must not raise


def test_non_dict_root_is_left_alone_unless_secret_shaped():
    assert redact("hello") == "hello"
    assert redact("Bearer sk-ant-longlonglonglong") == REDACTED
    assert redact([1, 2, 3]) == [1, 2, 3]
