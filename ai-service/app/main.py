"""
Medimatch BD AI matching service.

Laravel sends a search phrase and listings that have ALREADY passed the safety rules
(public, eligible, not expired, not the user's own). This service only puts them in order.
It never adds listings, never diagnoses, never recommends treatment and never decides
whether a medicine is suitable for a person.

Run:   .venv/bin/uvicorn app.main:app --port 8001     (POST /match is the endpoint Laravel calls)
Docs:  http://127.0.0.1:8001/docs
"""

import secrets
from contextlib import asynccontextmanager

from fastapi import Depends, FastAPI, Header, HTTPException

from app.catalogue import get_catalogue
from app.matching import match as run_match
from app.config import settings
from app.ranking import keyword, semantic
from app.schemas import MatchRequest, MatchResponse, NormalizeRequest, ParsedQueryOut, RankedItem, RankRequest, RankResponse
from app.text import parse

VERSION = "0.2.0"

@asynccontextmanager
async def lifespan(_: FastAPI):
    # Load the model at start-up so the first search is not slow. If it fails, /match falls back to keywords.
    if settings.preload_model:
        try:
            semantic.load(settings.semantic_model)
        except Exception:  # noqa: BLE001 - logged by uvicorn; the service still starts
            pass
    yield


app = FastAPI(
    lifespan=lifespan,
    title="Medimatch BD matching service",
    version=VERSION,
    description="Ranks pre-filtered medicine listings for a search phrase. Academic prototype; not medical advice.",
)


def require_api_key(x_api_key: str | None = Header(default=None)) -> None:
    """When MEDIMATCH_AI_API_KEY is set, every request must send the same value in X-Api-Key."""
    if settings.api_key and not (x_api_key and secrets.compare_digest(x_api_key, settings.api_key)):
        raise HTTPException(status_code=401, detail="Missing or invalid API key.")


def parsed_out(p) -> ParsedQueryOut:
    return ParsedQueryOut(normalized=p.normalized, brands=p.brands, generics=p.generics, strengths=p.strengths, form=p.form, corrections=p.corrections)


@app.get("/health")
def health() -> dict:
    catalogue = get_catalogue()
    return {
        "status": "ok",
        "version": VERSION,
        "semantic_model": settings.semantic_model,
        # Is the model already in memory? It loads on the first /match request (or at start-up, see app/main.py).
        "model_loaded": semantic.load.cache_info().currsize > 0,
        "catalogue_medicines": len(catalogue.medicines),
    }


@app.post("/match", response_model=MatchResponse, dependencies=[Depends(require_api_key)])
def match(body: MatchRequest) -> MatchResponse:
    """Rank Laravel's pre-filtered candidates: strict identity rules first, then semantic similarity."""
    outcome = run_match(body.query, body.candidates)

    return MatchResponse(
        method=outcome.method,
        degraded=outcome.degraded,
        query=parsed_out(outcome.parsed),
        excluded=outcome.rules.excluded,
        results=[RankedItem(id=i, score=round(s, 4)) for i, s in outcome.ranked[: body.limit]],
    )


@app.post("/normalize", response_model=ParsedQueryOut, dependencies=[Depends(require_api_key)])
def normalize(body: NormalizeRequest) -> ParsedQueryOut:
    p = parse(body.text)
    return ParsedQueryOut(normalized=p.normalized, brands=p.brands, generics=p.generics, strengths=p.strengths, form=p.form, corrections=p.corrections)


@app.post("/rank", response_model=RankResponse, dependencies=[Depends(require_api_key)])
def rank(body: RankRequest) -> RankResponse:
    """Keyword ranking WITHOUT the identity rules. Kept for comparison and debugging; Laravel uses /match."""
    ranked = keyword.rank(body.query, body.candidates, normalized=body.method == "keyword")
    p = parse(body.query)

    return RankResponse(
        method=body.method,
        query=ParsedQueryOut(normalized=p.normalized, brands=p.brands, generics=p.generics, strengths=p.strengths, form=p.form, corrections=p.corrections),
        results=[RankedItem(id=i, score=round(s, 4)) for i, s in ranked[: body.limit]],
    )
