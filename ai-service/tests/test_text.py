import pytest

from app.text import parse, strength_numbers


@pytest.mark.parametrize("query, brand, generic, strengths, form", [
    ("Napa 500mg tab", "napa", "paracetamol", [500.0], "tablet"),
    ("NAPA 500 MG TABLET", "napa", "paracetamol", [500.0], "tablet"),
    ("seclo 20 caps", "seclo", "omeprazole", [20.0], "capsule"),
    ("napa extend 665", "napa extend", "paracetamol", [665.0], None),
    ("ace plus 500+65", "ace plus", "paracetamol + caffeine", [500.0, 65.0], None),
    ("orsaline", "orsaline-n", "oral rehydration salts", [], None),
    ("azmasol 100mcg inh", "azmasol", "salbutamol", [100.0], "inhaler"),
])
def test_brands_strengths_and_forms_are_recognised(query, brand, generic, strengths, form):
    p = parse(query)
    assert brand in p.brands
    assert generic in p.generics
    assert p.strengths == strengths
    assert p.form == form


def test_a_longer_brand_hides_the_shorter_one_it_contains():
    assert parse("napa extend").brands == ["napa extend"]
    assert parse("ace plus").brands == ["ace plus"]


@pytest.mark.parametrize("typo, fixed", [("paracetemol", "paracetamol"), ("omiprazole", "omeprazole"), ("metfromin", "metformin"), ("potasium", "potassium")])
def test_common_misspellings_are_corrected(typo, fixed):
    assert parse(f"{typo} 500").corrections == {typo: fixed}


def test_short_or_unrelated_words_are_not_forced_into_medicine_names():
    p = parse("fever")
    assert p.generics == [] and p.brands == []
    assert parse("abc").corrections == {}


def test_bangla_script_and_digits_are_understood():
    p = parse("নাপা ৫০০ ট্যাবলেট")
    assert p.brands == ["napa"]
    assert p.strengths == [500.0]
    assert p.form == "tablet"


def test_banglish_filler_words_are_ignored():
    p = parse("amar napa 500 lagbe")
    assert p.tokens == ["napa"]


def test_strength_numbers_are_read_from_catalogue_strengths():
    assert strength_numbers("500 mg + 65 mg") == [500.0, 65.0]
    assert strength_numbers("120 mg / 5 ml") == [120.0, 5.0]
    assert strength_numbers("10.25 g") == [10.25]
