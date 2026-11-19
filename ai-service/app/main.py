"""
Medimatch BD AI matching service.

Laravel sends a search phrase and listings that have ALREADY passed the safety rules
(public, eligible, not expired, not the user's own). This service only puts them in order.
It never adds listings, never diagnoses, never recommends treatment and never decides
whether a medicine is suitable for a person.

Run:   .venv/bin/uvicorn app.main:app --port 8001
Docs:  http://127.0.0.1:8001/docs
"""

import secrets

from fastapi import Depends, FastAPI, Header, HTTPException

from app.catalogue import get_catalogue
from app.config import settings
from app.ranking import keyword
from app.schemas import NormalizeRequest, ParsedQueryOut, RankedItem, RankRequest, RankResponse
from app.text import parse

VERSION = "0.1.0"

app = FastAPI(
    title="Medimatch BD matching service",
    version=VERSION,
    description="Ranks pre-filtered medicine listings for a search phrase. Academic prototype; not medical advice.",
)


def require_api_key(x_api_key: str | None = Header(default=None)) -> None:
    """When MEDIMATCH_AI_API_KEY is set, every request must send the same value in X-Api-Key."""
    if settings.api_key and not (x_api_key and secrets.compare_digest(x_api_key, settings.api_key)):
        raise HTTPException(status_code=401, detail="Missing or invalid API key.")


@app.get("/health")
def health() -> dict:
    catalogue = get_catalogue()
    return {"status": "ok", "version": VERSION, "methods": ["keyword", "keyword_raw"], "catalogue_medicines": len(catalogue.medicines)}


@app.post("/normalize", response_model=ParsedQueryOut, dependencies=[Depends(require_api_key)])
def normalize(body: NormalizeRequest) -> ParsedQueryOut:
    p = parse(body.text)
    return ParsedQueryOut(normalized=p.normalized, brands=p.brands, generics=p.generics, strengths=p.strengths, form=p.form, corrections=p.corrections)


@app.post("/rank", response_model=RankResponse, dependencies=[Depends(require_api_key)])
def rank(body: RankRequest) -> RankResponse:
    ranked = keyword.rank(body.query, body.candidates, normalized=body.method == "keyword")
    p = parse(body.query)

    return RankResponse(
        method=body.method,
        query=ParsedQueryOut(normalized=p.normalized, brands=p.brands, generics=p.generics, strengths=p.strengths, form=p.form, corrections=p.corrections),
        results=[RankedItem(id=i, score=round(s, 4)) for i, s in ranked[: body.limit]],
    )
