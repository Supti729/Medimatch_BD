"""
Semantic ranking with multilingual sentence-embedding models.

The query and each listing are turned into vectors; listings are ordered by cosine similarity.
This finds the same medicine written differently (other languages, spellings, word order).
It is only used to ORDER listings that already passed app.rules; it never decides identity alone.

Models compared in the evaluation (both small enough for a CPU):
    e5      intfloat/multilingual-e5-small        (expects "query: " / "passage: " prefixes)
    minilm  sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2
"""

import os
import threading
from functools import lru_cache

from app.config import settings
from app.schemas import Candidate

# Models live in ai-service/models_cache (ignored by git). Set before the Hugging Face libraries are imported.
os.environ.setdefault("HF_HOME", str(settings.models_dir))
os.environ.setdefault("HF_HUB_DISABLE_TELEMETRY", "1")

MODELS = {
    "e5": ("intfloat/multilingual-e5-small", "query: ", "passage: "),
    "minilm": ("sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2", "", ""),
}

_lock = threading.Lock()


@lru_cache(maxsize=2)
def load(model_key: str):
    """Load a model once and keep it in memory. Raises if it cannot be loaded."""
    from sentence_transformers import SentenceTransformer  # imported here so keyword-only use stays light

    name = MODELS[model_key][0]
    with _lock:
        return SentenceTransformer(name, device="cpu")


def listing_passage(candidate: Candidate) -> str:
    return f"{candidate.brand_name} {candidate.generic_name} {candidate.strength} {candidate.dosage_form}"


def rank(query_text: str, candidates: list[Candidate], model_key: str = "e5") -> list[tuple[int, float]]:
    if not candidates:
        return []
    _, query_prefix, passage_prefix = MODELS[model_key]
    model = load(model_key)

    vectors = model.encode(
        [query_prefix + query_text] + [passage_prefix + listing_passage(c) for c in candidates],
        normalize_embeddings=True,
        convert_to_numpy=True,
        show_progress_bar=False,
    )
    scores = vectors[1:] @ vectors[0]
    return sorted(((c.id, float(s)) for c, s in zip(candidates, scores)), key=lambda item: (-item[1], item[0]))
