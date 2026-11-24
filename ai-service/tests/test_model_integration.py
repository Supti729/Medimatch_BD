"""
Runs the REAL e5 model. Skipped automatically when the model has not been downloaded
(.venv/bin/python -m app.download_models), so the other tests stay fast and offline.
"""

import pytest

from app.config import settings
from app.matching import match
from app.schemas import Candidate

MODEL_PRESENT = (settings.models_dir / "hub" / "models--intfloat--multilingual-e5-small").exists()
pytestmark = pytest.mark.skipif(not MODEL_PRESENT, reason="e5 model not downloaded")

CANDIDATES = [
    Candidate(id=1, brand_name="Napa", generic_name="Paracetamol", strength="500 mg", dosage_form="Tablet"),
    Candidate(id=2, brand_name="Ace", generic_name="Paracetamol", strength="500 mg", dosage_form="Tablet"),
    Candidate(id=3, brand_name="Napa Extra", generic_name="Paracetamol + Caffeine", strength="500 mg + 65 mg", dosage_form="Tablet"),
    Candidate(id=4, brand_name="ORSaline-N", generic_name="Oral Rehydration Salts", strength="10.25 g", dosage_form="Sachet"),
]


def test_the_real_model_ranks_a_bangla_search():
    outcome = match("নাপা ৫০০ ট্যাবলেট", CANDIDATES)
    assert outcome.method == "semantic:e5" and not outcome.degraded
    assert [i for i, _ in outcome.ranked][0] == 1
    assert 3 not in [i for i, _ in outcome.ranked]


def test_the_real_model_finds_a_medicine_the_rules_could_not_identify():
    outcome = match("ors packet", CANDIDATES)   # "ors" is not a catalogue brand, so no rule applies
    assert [i for i, _ in outcome.ranked][0] == 4
