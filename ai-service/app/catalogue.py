"""The medicine catalogue exported from Laravel (php artisan ai:export-catalogue)."""

import json
from dataclasses import dataclass, field
from functools import lru_cache
from pathlib import Path

from app.config import settings


@dataclass(frozen=True)
class CatalogueMedicine:
    id: int
    generic_name: str
    strength: str
    dosage_form: str
    requires_prescription: bool
    listable: bool
    brands: tuple[str, ...]


@dataclass
class Catalogue:
    medicines: list[CatalogueMedicine]
    # lower-case brand name -> generic names it belongs to (one brand can cover several strengths/forms)
    brand_to_generics: dict[str, set[str]] = field(default_factory=dict)
    # every word that appears in a generic or brand name, used for spelling correction
    vocabulary: set[str] = field(default_factory=set)
    generic_names: set[str] = field(default_factory=set)

    @classmethod
    def load(cls, path: Path) -> "Catalogue":
        raw = json.loads(path.read_text(encoding="utf-8"))
        medicines = [
            CatalogueMedicine(
                id=m["id"],
                generic_name=m["generic_name"],
                strength=m["strength"],
                dosage_form=m["dosage_form"],
                requires_prescription=bool(m["requires_prescription"]),
                listable=bool(m["listable"]),
                brands=tuple(m["brands"]),
            )
            for m in raw["medicines"]
        ]

        catalogue = cls(medicines=medicines)
        for medicine in medicines:
            generic = medicine.generic_name.lower()
            catalogue.generic_names.add(generic)
            catalogue.vocabulary.update(_words(generic))
            for brand in medicine.brands:
                catalogue.brand_to_generics.setdefault(brand.lower(), set()).add(generic)
                catalogue.vocabulary.update(_words(brand.lower()))
        return catalogue


def _words(text: str) -> list[str]:
    return [w for w in "".join(c if c.isalnum() else " " for c in text).split() if len(w) > 1]


@lru_cache(maxsize=1)
def get_catalogue() -> Catalogue:
    return Catalogue.load(settings.catalogue_path)
