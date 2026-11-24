"""
Text clean-up for medicine searches.

Turns what people type into a structured description:

    "NAPA 500mg tab"      -> brand napa, generic paracetamol, strength 500 mg, form tablet
    "paracetemol 500"     -> spelling corrected to paracetamol, strength 500 (unit unknown)
    "নাপা ৫০০ ট্যাবলেট"   -> Bangla script and digits mapped to napa 500 tablet

It only reads medicine names, strengths and dosage forms. It never interprets symptoms
or diseases, and it never decides whether a medicine is suitable for anyone.
"""

import difflib
import json
import re
import unicodedata
from dataclasses import dataclass, field
from functools import lru_cache

from app.catalogue import Catalogue, get_catalogue
from app.config import settings

BANGLA_DIGITS = str.maketrans("০১২৩৪৫৬৭৮৯", "0123456789")

# Different ways people write a dosage form -> the catalogue's form name
FORM_SYNONYMS = {
    "tablet": {"tab", "tabs", "tablet", "tablets", "tb", "tbl"},
    "capsule": {"cap", "caps", "capsule", "capsules", "cp"},
    "syrup": {"syp", "syr", "syrup", "syrups"},
    "suspension": {"susp", "suspension"},
    "sachet": {"sachet", "sachets"},
    "drops": {"drop", "drops"},
    "cream": {"cream", "crm"},
    "inhaler": {"inhaler", "inh", "puffer"},
    "injection": {"inj", "injection"},
}
FORM_OF_WORD = {word: form for form, words in FORM_SYNONYMS.items() for word in words}

UNIT_ALIASES = {"mg": "mg", "mgs": "mg", "milligram": "mg", "mcg": "mcg", "µg": "mcg", "ug": "mcg", "g": "g", "gm": "g", "ml": "ml", "iu": "iu", "%": "%"}

# "500mg", "500 mg", "0.5 g", "120mg/5ml", "500+65 mg"
STRENGTH_PATTERN = re.compile(r"(\d+(?:\.\d+)?)\s*(mg|mgs|milligram|mcg|µg|ug|gm|g|ml|iu|%)(?![a-z])")
NUMBER_PATTERN = re.compile(r"\b\d+(?:\.\d+)?\b")

# Short everyday words that are never medicine names (English and Banglish)
STOP_WORDS = {"i", "need", "want", "a", "an", "the", "for", "of", "and", "or", "pls", "please", "plz", "any", "some",
              "amar", "lagbe", "ache", "dorkar", "er", "ki", "keu", "hobe", "dite", "parben", "available", "medicine",
              "osudh", "ousudh", "oshudh", "strip", "pata", "pc", "pcs", "piece", "pieces", "x"}


@dataclass
class ParsedQuery:
    original: str
    normalized: str
    tokens: list[str]                                   # cleaned words, spelling corrected
    brands: list[str] = field(default_factory=list)     # brand names recognised in the query
    generics: list[str] = field(default_factory=list)   # generic names named directly or via a brand
    strengths: list[float] = field(default_factory=list)  # numbers of any strength mentioned (500, 20, 0.5)
    strength_units: list[str] = field(default_factory=list)
    form: str | None = None
    corrections: dict[str, str] = field(default_factory=dict)  # misspelled word -> corrected word

    def expanded_tokens(self) -> list[str]:
        """Tokens plus the generic names of recognised brands (for matching brand searches to generic text)."""
        extra = [w for generic in self.generics for w in generic.split()]
        return self.tokens + [w for w in extra if w not in self.tokens]


@lru_cache(maxsize=1)
def bangla_terms() -> dict[str, str]:
    return json.loads(settings.bangla_terms_path.read_text(encoding="utf-8"))["terms"]


def basic_clean(text: str) -> str:
    """Lower-case, normalise Unicode, convert Bangla digits and Bangla medicine words."""
    text = unicodedata.normalize("NFKC", text).lower().translate(BANGLA_DIGITS)
    for bangla, english in sorted(bangla_terms().items(), key=lambda kv: -len(kv[0])):
        text = text.replace(bangla, f" {english} ")
    text = re.sub(r"(\d)\s*\+\s*(\d)", r"\1 \2", text)          # "500+65" -> "500 65"
    # Keep letters, digits, ., %, / and the whole Bangla Unicode block (\u0980-\u09FF). Bangla vowel signs are
    # combining marks, not "word" characters, so without the block they would be stripped and words cut apart.
    text = re.sub(r"[^\w\s.%/µ\u0980-\u09FF]", " ", text)
    text = text.replace("/", " ")
    return re.sub(r"\s+", " ", text).strip()


def parse(text: str, catalogue: Catalogue | None = None) -> ParsedQuery:
    catalogue = catalogue or get_catalogue()
    cleaned = basic_clean(text)

    # Strengths with units first, then bare numbers ("napa 500")
    strengths, units = [], []
    for number, unit in STRENGTH_PATTERN.findall(cleaned):
        strengths.append(float(number))
        units.append(UNIT_ALIASES[unit])
    without_units = STRENGTH_PATTERN.sub(" ", cleaned)
    for number in NUMBER_PATTERN.findall(without_units):
        if float(number) not in strengths:
            strengths.append(float(number))

    # Words: drop numbers, units and filler words; recognise forms; correct spelling against the catalogue
    tokens, corrections, form = [], {}, None
    vocabulary = sorted(catalogue.vocabulary)
    for word in re.sub(r"\d+(?:\.\d+)?", " ", without_units).replace(".", " ").split():
        if word in STOP_WORDS or word in UNIT_ALIASES or len(word) < 2:
            continue
        if word in FORM_OF_WORD:
            form = form or FORM_OF_WORD[word]
            continue
        if word not in catalogue.vocabulary and len(word) >= 4:
            match = difflib.get_close_matches(word, vocabulary, n=1, cutoff=0.8)
            if match:
                corrections[word] = match[0]
                word = match[0]
        tokens.append(word)

    # Brands and generics, including multi-word names ("napa extend", "losartan potassium").
    # A name matches when all its words (ignoring one-letter parts like the "N" in "ORSaline-N") are in the query.
    present = set(tokens)
    brands = sorted((b for b in catalogue.brand_to_generics if set(name_words(b)) <= present and name_words(b)), key=len, reverse=True)
    # A longer brand ("napa extend") hides the shorter one it contains ("napa")
    brands = [b for b in brands if not any(b != longer and set(name_words(b)) < set(name_words(longer)) for longer in brands)]
    generics = {g for g in catalogue.generic_names if name_words(g) and set(name_words(g)) <= present}
    # A combination named in full ("paracetamol caffeine") hides its single ingredient ("paracetamol")
    generics = {g for g in generics if not any(g != longer and set(name_words(g)) < set(name_words(longer)) for longer in generics)}
    for brand in brands:
        generics.update(catalogue.brand_to_generics[brand])

    return ParsedQuery(
        original=text,
        normalized=" ".join(tokens + ([form] if form else []) + [_fmt(n) for n in strengths]),
        tokens=tokens,
        brands=brands,
        generics=sorted(generics),
        strengths=strengths,
        strength_units=units,
        form=form,
        corrections=corrections,
    )


def name_words(name: str) -> list[str]:
    """Words of a brand or generic name, without punctuation or one-letter parts."""
    return [w for w in re.sub(r"[^a-z0-9]+", " ", name.lower()).split() if len(w) > 1]


def strength_numbers(strength: str) -> list[float]:
    """Numbers in a catalogue strength: '500 mg + 65 mg' -> [500, 65], '120 mg / 5 ml' -> [120, 5]."""
    return [float(n) for n in NUMBER_PATTERN.findall(strength.translate(BANGLA_DIGITS))]


def _fmt(number: float) -> str:
    return str(int(number)) if number == int(number) else str(number)
