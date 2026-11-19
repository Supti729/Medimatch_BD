# Medimatch BD matching service

A small Python (FastAPI) service that **ranks** medicine listings for a search phrase.
It is part of Medimatch BD, an academic prototype.

## What it does and does not do

Laravel sends a search phrase and a list of listings that have **already passed the safety rules**
(public, eligible, not expired, not the user's own). This service only puts them in order.

It never:

- adds listings that Laravel did not send,
- diagnoses, prescribes or recommends treatment,
- decides whether a medicine is suitable for a person,
- approves or rejects prescriptions,
- overrides the safety or eligibility rules in Laravel.

It only reads **medicine names, strengths and dosage forms**, not symptoms.

## Run it

```bash
cd ai-service
python3 -m venv .venv
.venv/bin/pip install -r requirements-dev.txt
cp .env.example .env                       # optional: set MEDIMATCH_AI_API_KEY
.venv/bin/uvicorn app.main:app --port 8001
```

Open http://127.0.0.1:8001/docs for interactive API documentation.

After changing the medicine catalogue in Laravel, export it again from the project root:

```bash
php artisan ai:export-catalogue             # writes ai-service/data/catalogue.json
```

## API

| Method | Path | Purpose |
|---|---|---|
| GET | `/health` | Status, version, number of catalogue medicines. Open without a key. |
| POST | `/normalize` | How a search phrase is understood: brand, generic, strength, form, spelling fixes. |
| POST | `/rank` | Rank candidate listings for a phrase. |

`POST /rank` example:

```json
{
  "query": "নাপা ৫০০ ট্যাবলেট",
  "limit": 10,
  "method": "keyword",
  "candidates": [
    {"id": 1, "brand_name": "Napa", "generic_name": "Paracetamol", "strength": "500 mg", "dosage_form": "Tablet"}
  ]
}
```

Limits: up to 200 candidates, 200-character phrases, at most 50 results. When `MEDIMATCH_AI_API_KEY` is set,
every request except `/health` must send it in the `X-Api-Key` header.

## Text clean-up (`app/text.py`)

| People type | Understood as |
|---|---|
| `Napa 500mg tab` | brand Napa → generic paracetamol, 500, tablet |
| `paracetemol`, `omiprazole`, `potasium` | spelling corrected against catalogue words |
| `নাপা ৫০০ ট্যাবলেট` | Bangla words and digits converted (`data/bangla_terms.json`) |
| `amar napa 500 lagbe` | Banglish filler words ignored |
| `tab`, `caps`, `syp`, `inh` | tablet, capsule, syrup, inhaler |
| `500+65`, `120mg/5ml` | strengths read as numbers |

## Evaluation (A1 baseline)

`evaluation/dataset.json`: 31 listings and 70 search phrases in 10 categories. For each phrase, the intended
medicine (generic name, and strength or form when the phrase states them) was written by hand. A listing is
relevant when it has that generic name and, if given, that strength and form.

Run: `.venv/bin/python -m evaluation.evaluate` (results are written to `evaluation/results/`).

Measured results (every phrase ranked against all 31 listings):

| Method | Queries | Precision@5 (best possible) | Recall@5 | MRR | Queries with no relevant result |
|---|---|---|---|---|---|
| keyword_raw | 70 | 0.311 (0.434) | 0.774 | 0.795 | 10 |
| keyword | 70 | 0.406 (0.434) | 0.966 | 1.000 | 0 |

MRR by category:

| Category | Queries | keyword_raw | keyword |
|---|---|---|---|
| exact | 10 | 1.000 | 1.000 |
| generic | 13 | 1.000 | 1.000 |
| brand_only | 7 | 1.000 | 1.000 |
| abbreviation | 6 | 0.732 | 1.000 |
| misspelling | 10 | 0.600 | 1.000 |
| bangla | 8 | 0.000 | 1.000 |
| banglish | 5 | 1.000 | 1.000 |
| strength | 4 | 0.812 | 1.000 |
| form | 3 | 1.000 | 1.000 |
| combination | 4 | 1.000 | 1.000 |

- **keyword_raw**: BM25 on plain words, like a basic database text search.
- **keyword**: the same BM25 after the text clean-up above.
- **Precision@5**: most phrases have fewer than 5 relevant listings, so even a perfect ranking scores below 1.0; the best possible value is shown in brackets.

### What the numbers mean

- Plain keyword search found **nothing relevant** for all 8 Bangla phrases and 2 misspellings.
- With text clean-up every phrase found a relevant listing first (MRR 1.000). **This is optimistic**: the clean-up
  rules (Bangla word list, form abbreviations, filler words) and the test phrases were written by the same team,
  so the test cannot show how it handles phrasing nobody anticipated. A2 adds held-out phrases for that.
- Keyword ranking still **mixes up different medicines that share a word**. For "Napa 500 mg tablet" the top 5
  included Napa Extra (paracetamol + caffeine, a different medicine) and Napa Extend (665 mg, a different strength)
  above Ace 500 mg, which is the correct medicine. A2 addresses this with strict strength and form rules plus
  semantic ranking.

## Tests

```bash
.venv/bin/python -m pytest
```
