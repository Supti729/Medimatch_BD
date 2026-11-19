import pytest
from fastapi.testclient import TestClient

from app.config import settings
from app.main import app

client = TestClient(app)

CANDIDATES = [
    {"id": 11, "brand_name": "Napa", "generic_name": "Paracetamol", "strength": "500 mg", "dosage_form": "Tablet"},
    {"id": 12, "brand_name": "Seclo", "generic_name": "Omeprazole", "strength": "20 mg", "dosage_form": "Capsule"},
]


@pytest.fixture(autouse=True)
def no_api_key(monkeypatch):
    monkeypatch.setattr(settings, "api_key", "")


def test_health():
    body = client.get("/health").json()
    assert body["status"] == "ok"
    assert body["catalogue_medicines"] > 0


def test_rank_orders_the_candidates_it_was_given():
    response = client.post("/rank", json={"query": "napa 500 tab", "candidates": CANDIDATES})
    assert response.status_code == 200
    body = response.json()
    assert [r["id"] for r in body["results"]] == [11]
    assert body["query"]["generics"] == ["paracetamol"]


def test_rank_respects_the_limit():
    many = [dict(c, id=100 + n) for n, c in enumerate(CANDIDATES * 10)]
    assert len(client.post("/rank", json={"query": "paracetamol", "candidates": many, "limit": 3}).json()["results"]) == 3


@pytest.mark.parametrize("payload", [
    {"query": "", "candidates": CANDIDATES},
    {"query": "   ", "candidates": CANDIDATES},
    {"query": "x" * 201, "candidates": CANDIDATES},
    {"query": "napa", "candidates": [{"id": 0, "brand_name": "Napa", "generic_name": "P", "strength": "1", "dosage_form": "T"}]},
    {"query": "napa", "candidates": [{"id": 1}]},
    {"query": "napa", "candidates": CANDIDATES, "limit": 500},
    {"query": "napa", "candidates": CANDIDATES, "method": "magic"},
    {"query": "napa", "candidates": [CANDIDATES[0]] * 201},
])
def test_invalid_requests_are_rejected(payload):
    assert client.post("/rank", json=payload).status_code == 422


def test_api_key_is_required_when_configured(monkeypatch):
    monkeypatch.setattr(settings, "api_key", "test-secret")

    assert client.post("/rank", json={"query": "napa", "candidates": CANDIDATES}).status_code == 401
    assert client.post("/rank", json={"query": "napa", "candidates": CANDIDATES}, headers={"X-Api-Key": "wrong"}).status_code == 401
    assert client.post("/rank", json={"query": "napa", "candidates": CANDIDATES}, headers={"X-Api-Key": "test-secret"}).status_code == 200
    assert client.get("/health").status_code == 200  # health stays open for monitoring


def test_normalize_endpoint():
    body = client.post("/normalize", json={"text": "paracetemol 500 syp"}).json()
    assert body["generics"] == ["paracetamol"]
    assert body["form"] == "syrup"
    assert body["corrections"] == {"paracetemol": "paracetamol"}
