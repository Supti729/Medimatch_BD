"""
Keyword baseline: BM25 scoring of listing text against the query words.

Two versions, so the evaluation can show what text clean-up adds:
  raw         plain lower-case words, like a basic database text search
  normalized  words after app.text.parse(): spelling fixed, Bangla converted,
              brand names expanded to their generic name, strengths written one way

Only listings with a score above zero are returned, like a search engine would.
"""

import math
import re
from collections import Counter

from app.schemas import Candidate
from app.text import FORM_OF_WORD, basic_clean, parse

K1 = 1.2
B = 0.75


def listing_text(candidate: Candidate) -> str:
    return f"{candidate.brand_name} {candidate.generic_name} {candidate.strength} {candidate.dosage_form}"


def raw_tokens(text: str) -> list[str]:
    return re.findall(r"[a-z0-9]+", text.lower())


def normalized_tokens(text: str) -> list[str]:
    """Listing text through the same clean-up as queries: '500 mg' -> '500', 'Tablet' -> 'tablet'."""
    cleaned = basic_clean(text)
    words = []
    for word in re.findall(r"[a-z0-9.]+", cleaned):
        word = word.strip(".")
        if not word or word in {"mg", "mcg", "g", "ml", "iu"}:
            continue
        words.append(FORM_OF_WORD.get(word, word))
    return words


def bm25(query_tokens: list[str], documents: list[list[str]]) -> list[float]:
    n = len(documents)
    if n == 0 or not query_tokens:
        return [0.0] * n
    avg_len = sum(len(d) for d in documents) / n or 1.0
    doc_freq = Counter(term for d in documents for term in set(d))
    scores = []
    for doc in documents:
        counts = Counter(doc)
        score = 0.0
        for term in set(query_tokens):
            tf = counts.get(term, 0)
            if tf == 0:
                continue
            idf = math.log(1 + (n - doc_freq[term] + 0.5) / (doc_freq[term] + 0.5))
            score += idf * tf * (K1 + 1) / (tf + K1 * (1 - B + B * len(doc) / avg_len))
        scores.append(score)
    return scores


def rank(query: str, candidates: list[Candidate], normalized: bool = True) -> list[tuple[int, float]]:
    if normalized:
        parsed = parse(query)
        numbers = [str(int(n)) if n == int(n) else str(n) for n in parsed.strengths]
        q_tokens = parsed.expanded_tokens() + numbers + ([parsed.form] if parsed.form else [])
        docs = [normalized_tokens(listing_text(c)) for c in candidates]
    else:
        q_tokens = raw_tokens(query)
        docs = [raw_tokens(listing_text(c)) for c in candidates]

    scored = [(c.id, s) for c, s in zip(candidates, bm25(q_tokens, docs)) if s > 0]
    return sorted(scored, key=lambda item: (-item[1], item[0]))
