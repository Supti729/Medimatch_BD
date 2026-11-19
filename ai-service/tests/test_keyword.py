from app.ranking import keyword
from app.schemas import Candidate

CORPUS = [
    Candidate(id=1, brand_name="Napa", generic_name="Paracetamol", strength="500 mg", dosage_form="Tablet"),
    Candidate(id=2, brand_name="Ace", generic_name="Paracetamol", strength="500 mg", dosage_form="Tablet"),
    Candidate(id=3, brand_name="Seclo", generic_name="Omeprazole", strength="20 mg", dosage_form="Capsule"),
]


def test_only_listings_that_match_any_word_are_returned():
    assert [i for i, _ in keyword.rank("seclo", CORPUS)] == [3]
    assert keyword.rank("zzzz", CORPUS) == []


def test_a_brand_search_also_finds_other_brands_of_the_same_generic_after_normalisation():
    assert {i for i, _ in keyword.rank("napa", CORPUS, normalized=True)} == {1, 2}
    assert {i for i, _ in keyword.rank("napa", CORPUS, normalized=False)} == {1}


def test_bangla_only_works_after_normalisation():
    assert keyword.rank("সেকলো", CORPUS, normalized=False) == []
    assert [i for i, _ in keyword.rank("সেকলো", CORPUS, normalized=True)] == [3]


def test_ranking_never_invents_listings():
    ids = {i for i, _ in keyword.rank("paracetamol omeprazole napa seclo", CORPUS)}
    assert ids <= {c.id for c in CORPUS}
