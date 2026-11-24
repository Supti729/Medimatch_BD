"""
Strict identity rules, applied BEFORE any ranking.

Text similarity alone must never decide that two medicines are the same. So when a search
names a medicine, a strength or a dosage form, listings that differ are removed, whatever
their similarity score:

    different medicine  "napa 500" (paracetamol) never returns Napa Extra (paracetamol + caffeine)
    different strength  "omeprazole 40mg" never returns 20 mg capsules
    different form      "napa syrup" never returns tablets

These rules only REMOVE candidates. Laravel has already removed listings that are not public,
not eligible or not available; nothing here can bring them back.
"""

from collections import Counter
from dataclasses import dataclass, field

from app.catalogue import Catalogue, get_catalogue
from app.schemas import Candidate
from app.text import FORM_OF_WORD, ParsedQuery, strength_numbers


@dataclass
class RuleResult:
    allowed: list[Candidate]
    excluded: dict[str, int] = field(default_factory=dict)  # reason -> number of listings removed
    strengths_used: list[float] = field(default_factory=list)


def stated_strengths(parsed: ParsedQuery, catalogue: Catalogue) -> list[float]:
    """
    Numbers that really are strengths. Numbers written with a unit ("500mg") always count.
    A bare number ("napa 500", "2 strips") counts only if a catalogue medicine of the searched
    generic (or any medicine, when none is named) comes in that strength, so quantities like
    "2 strips" or "x 10" are not mistaken for strengths.
    """
    with_units = parsed.strengths[: len(parsed.strength_units)]
    bare = parsed.strengths[len(parsed.strength_units):]

    pool = [m for m in catalogue.medicines if not parsed.generics or m.generic_name.lower() in parsed.generics]
    known = {n for m in pool for n in strength_numbers(m.strength)}

    return with_units + [n for n in bare if n in known]


def apply(parsed: ParsedQuery, candidates: list[Candidate], catalogue: Catalogue | None = None) -> RuleResult:
    catalogue = catalogue or get_catalogue()
    strengths = stated_strengths(parsed, catalogue)
    allowed, excluded = [], Counter()

    for candidate in candidates:
        if parsed.generics and candidate.generic_name.lower() not in parsed.generics:
            excluded["different medicine"] += 1
        elif strengths and not all(n in strength_numbers(candidate.strength) for n in strengths):
            excluded["different strength"] += 1
        elif parsed.form and FORM_OF_WORD.get(candidate.dosage_form.lower(), candidate.dosage_form.lower()) != parsed.form:
            excluded["different form"] += 1
        else:
            allowed.append(candidate)

    return RuleResult(allowed=allowed, excluded=dict(excluded), strengths_used=strengths)
