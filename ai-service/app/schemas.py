"""Request and response shapes of the API. Pydantic validates every request against these."""

from typing import Literal

from pydantic import BaseModel, Field, field_validator

from app.config import settings


class Candidate(BaseModel):
    """A listing Laravel has ALREADY checked against the safety rules (public, eligible, available)."""

    id: int = Field(ge=1)
    brand_name: str = Field(min_length=1, max_length=100)
    generic_name: str = Field(min_length=1, max_length=150)
    strength: str = Field(min_length=1, max_length=50)
    dosage_form: str = Field(min_length=1, max_length=30)


class RankRequest(BaseModel):
    query: str = Field(min_length=1, max_length=settings.max_query_length)
    candidates: list[Candidate] = Field(max_length=settings.max_candidates)
    limit: int = Field(default=10, ge=1, le=50)
    method: Literal["keyword", "keyword_raw"] = "keyword"

    @field_validator("query")
    @classmethod
    def not_blank(cls, value: str) -> str:
        if not value.strip():
            raise ValueError("The query cannot be empty.")
        return value.strip()


class RankedItem(BaseModel):
    id: int
    score: float


class ParsedQueryOut(BaseModel):
    normalized: str
    brands: list[str]
    generics: list[str]
    strengths: list[float]
    form: str | None
    corrections: dict[str, str]


class RankResponse(BaseModel):
    method: str
    query: ParsedQueryOut
    results: list[RankedItem]


class NormalizeRequest(BaseModel):
    text: str = Field(min_length=1, max_length=settings.max_query_length)
