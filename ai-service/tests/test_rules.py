import pytest

from app.rules import apply, stated_strengths
from app.catalogue import get_catalogue
from app.schemas import Candidate
from app.text import parse

C = [
    Candidate(id=1, brand_name="Napa", generic_name="Paracetamol", strength="500 mg", dosage_form="Tablet"),
    Candidate(id=2, brand_name="Napa Extend", generic_name="Paracetamol", strength="665 mg", dosage_form="Tablet"),
    Candidate(id=3, brand_name="Napa", generic_name="Paracetamol", strength="120 mg / 5 ml", dosage_form="Syrup"),
    Candidate(id=4, brand_name="Napa Extra", generic_name="Paracetamol + Caffeine", strength="500 mg + 65 mg", dosage_form="Tablet"),
    Candidate(id=5, brand_name="Seclo", generic_name="Omeprazole", strength="20 mg", dosage_form="Capsule"),
    Candidate(id=6, brand_name="Seclo", generic_name="Omeprazole", strength="40 mg", dosage_form="Capsule"),
]


def allowed(query):
    return [c.id for c in apply(parse(query), C).allowed]


def test_a_different_medicine_is_never_matched_even_with_the_same_brand_word():
    assert allowed("napa 500 tablet") == [1]           # not Napa Extra (paracetamol + caffeine)
    assert allowed("napa extra") == [4]


def test_a_different_strength_is_never_matched():
    assert allowed("omeprazole 40mg") == [6]
    assert allowed("napa extend 665") == [2]


def test_a_different_form_is_never_matched():
    assert allowed("napa syrup") == [3]
    assert allowed("seclo tablet") == []


def test_when_nothing_is_specified_everything_stays():
    assert allowed("medicine") == [1, 2, 3, 4, 5, 6]


def test_quantities_are_not_mistaken_for_strengths():
    catalogue = get_catalogue()
    assert stated_strengths(parse("napa 500 2 strips"), catalogue) == [500.0]
    assert allowed("napa 500 x 10") == [1]


def test_the_reasons_for_exclusion_are_reported():
    result = apply(parse("omeprazole 40mg"), C)
    assert result.excluded == {"different medicine": 4, "different strength": 1}


@pytest.mark.parametrize("query", ["napa", "seclo 20", "paracetamol syrup", "ace plus"])
def test_rules_only_ever_remove_candidates(query):
    ids = {c.id for c in apply(parse(query), C).allowed}
    assert ids <= {c.id for c in C}
