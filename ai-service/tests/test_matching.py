import pytest
from fastapi.testclient import TestClient

from app import matching
from app.config import settings
from app.main import app
from app.ranking import semantic

client = TestClient(app)  # used without "with", so the model is not loaded at start-up in these tests

CANDIDATES = [
    {"id": 1, "brand_name": "Napa", "generic_name": "Paracetamol", "strength": "500 mg", "dosage_form": "Tablet"},
    {"id": 2, "brand_name": "Ace", "generic_name": "Paracetamol", "strength": "500 mg", "dosage_form": "Tablet"},
    {"id": 3, "brand_name": "Napa Extra", "generic_name": "Paracetamol + Caffeine", "strength": "500 mg + 65 mg", "dosage_form": "Tablet"},
    {"id": 4, "brand_name": "Seclo", "generic_name": "Omeprazole", "strength": "20 mg", "dosage_form": "Capsule"},
]


@pytest.fixture(autouse=True)
def setup(monkeypatch):
    monkeypatch.setattr(settings, "api_key", "")

    # A stand-in for the embedding model: prefers the listing whose brand appears in the query.
    def fake_rank(query_text, candidates, model_key="e5"):
        scored = [(c.id, 0.9 if c.brand_name.lower() in query_text else 0.5) for c in candidates]
        return sorted(scored, key=lambda x: (-x[1], x[0]))

    monkeypatch.setattr(semantic, "rank", fake_rank)


def test_match_applies_the_rules_then_ranks():
    body = client.post("/match", json={"query": "ace 500 tab", "candidates": CANDIDATES}).json()

    assert body["method"] == "semantic:e5"
    assert body["degraded"] is False
    assert [r["id"] for r in body["results"]] == [2, 1]      # Napa Extra and Seclo removed by the rules
    assert body["excluded"] == {"different medicine": 2}


def test_match_falls_back_to_keywords_if_the_model_fails(monkeypatch):
    def broken(*args, **kwargs):
        raise RuntimeError("model missing")

    monkeypatch.setattr(semantic, "rank", broken)
    body = client.post("/match", json={"query": "seclo 20", "candidates": CANDIDATES}).json()

    assert body["method"] == "keyword"
    assert body["degraded"] is True
    assert [r["id"] for r in body["results"]] == [4]


def test_match_returns_nothing_when_no_candidate_passes_the_rules():
    body = client.post("/match", json={"query": "seclo syrup", "candidates": CANDIDATES}).json()
    assert body["results"] == []
    assert body["excluded"]["different medicine"] == 3


def test_match_never_returns_an_id_it_was_not_given():
    body = client.post("/match", json={"query": "paracetamol", "candidates": CANDIDATES, "limit": 50}).json()
    assert {r["id"] for r in body["results"]} <= {c["id"] for c in CANDIDATES}


def test_match_validates_input_and_requires_the_key(monkeypatch):
    assert client.post("/match", json={"query": "", "candidates": CANDIDATES}).status_code == 422
    assert client.post("/match", json={"query": "napa", "candidates": [CANDIDATES[0]] * 201}).status_code == 422

    monkeypatch.setattr(settings, "api_key", "secret")
    assert client.post("/match", json={"query": "napa", "candidates": CANDIDATES}).status_code == 401
    assert client.post("/match", json={"query": "napa", "candidates": CANDIDATES}, headers={"X-Api-Key": "secret"}).status_code == 200


def test_the_semantic_query_text_uses_the_cleaned_words():
    from app.text import parse
    assert matching.semantic_query_text(parse("নাপা ৫০০ ট্যাবলেট")) == "napa paracetamol 500 tablet"
