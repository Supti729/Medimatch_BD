"""
The matching pipeline used by POST /match:

    1. parse the search phrase           (app.text)
    2. remove listings that differ in medicine, strength or form   (app.rules)
    3. order the rest by meaning         (app.ranking.semantic), or by keywords if the model is unavailable
"""

import logging
from dataclasses import dataclass

from app.config import settings
from app.ranking import keyword, semantic
from app.rules import RuleResult, apply
from app.schemas import Candidate
from app.text import ParsedQuery, parse

log = logging.getLogger(__name__)


@dataclass
class MatchOutcome:
    parsed: ParsedQuery
    rules: RuleResult
    ranked: list[tuple[int, float]]
    method: str
    degraded: bool = False  # True when the embedding model could not be used and keyword ranking was used instead


def semantic_query_text(parsed: ParsedQuery) -> str:
    """The cleaned search for the embedding model: words (with brands expanded to generics), strengths and form."""
    numbers = [str(int(n)) if n == int(n) else str(n) for n in parsed.strengths]
    text = " ".join(parsed.expanded_tokens() + numbers + ([parsed.form] if parsed.form else []))
    return text or parsed.original


def match(query: str, candidates: list[Candidate], method: str = "semantic") -> MatchOutcome:
    parsed = parse(query)
    rules = apply(parsed, candidates)

    if method == "keyword" or not rules.allowed:
        return MatchOutcome(parsed, rules, keyword.rank(query, rules.allowed, normalized=True), "keyword")

    try:
        ranked = semantic.rank(semantic_query_text(parsed), rules.allowed, settings.semantic_model)
        return MatchOutcome(parsed, rules, ranked, f"semantic:{settings.semantic_model}")
    except Exception:  # model missing, out of memory, ... : keep the service working
        log.exception("Semantic ranking failed; falling back to keyword ranking")
        return MatchOutcome(parsed, rules, keyword.rank(query, rules.allowed, normalized=True), "keyword", degraded=True)
