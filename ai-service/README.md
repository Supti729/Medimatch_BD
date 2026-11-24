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
.venv/bin/pip install torch==2.15.0 --index-url https://download.pytorch.org/whl/cpu   # CPU-only PyTorch first (smaller)
.venv/bin/pip install -r requirements-dev.txt
.venv/bin/python -m app.download_models    # downloads multilingual-e5-small (~470 MB) into models_cache/
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
| POST | `/match` | **What Laravel uses.** Strict identity rules, then semantic ranking. |
| POST | `/rank` | Keyword ranking without rules. Kept for comparison and debugging. |

`POST /match` example:

```json
{
  "query": "নাপা ৫০০ ট্যাবলেট",
  "limit": 10,
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

## How matching works (`POST /match`)

1. **Clean up** the phrase (`app/text.py`).
2. **Strict identity rules** (`app/rules.py`) remove every listing whose medicine, strength or form differs from
   what the phrase states. A number counts as a strength only if a matching medicine really comes in that strength,
   so "2 strips" or "x 10" are not mistaken for strengths. Rules only remove; they never add.
3. **Semantic ranking** (`app/ranking/semantic.py`) orders what is left by meaning, using
   `intfloat/multilingual-e5-small` on the CPU.
4. If the model cannot be used, keyword ranking is used instead and the response says `"degraded": true`.

The response lists how many listings each rule removed, so results can always be explained.

## Evaluation

Two hand-labelled sets, ranked against the same 31 listings (`evaluation/dataset.json`):

- **Development set**: 70 phrases (`dataset.json`). The clean-up rules were written while looking at these, so results on it are optimistic.
- **Held-out set**: 30 phrases (`heldout.json`), written before the A2 rules, with phrasing the clean-up was not built for:
  salt names ("metformin hydrochloride"), brands missing from the catalogue ("ventolin"), unseen Bangla spellings,
  quantities mixed in ("2 strips"). The rules were not tuned after seeing these results. Written by the same author,
  so a teammate should add more phrases.

For each phrase, the intended medicine was written by hand; a listing is relevant when it has that generic name and,
if the phrase states them, that strength and form. Run: `.venv/bin/python -m evaluation.evaluate [--set heldout]`.

Methods:

| Method | What it is |
|---|---|
| keyword_raw | BM25 on plain words, like a basic database search (A1) |
| keyword | BM25 after text clean-up (A1) |
| e5_only / minilm_only | embedding model alone, on the phrase exactly as typed, no rules |
| keyword_rules / e5_rules / minilm_rules | strict identity rules first, then that ranking (**e5_rules is what /match uses**) |

Precision@5 cannot reach 1.0 when a phrase has fewer than 5 relevant listings; the best possible value is in brackets.
Timings are on this project's development laptop (CPU only); "only" methods compare against all 31 listings,
"rules" methods only against what the rules leave.

### Development set (70 phrases)

| Method | Queries | Precision@5 (best possible) | Recall@5 | MRR | No relevant result | ms / query |
|---|---|---|---|---|---|---|
| keyword_raw | 70 | 0.311 (0.434) | 0.774 | 0.795 | 10 | 0.1 |
| keyword | 70 | 0.406 (0.434) | 0.966 | 1.000 | 0 | 0.4 |
| e5_only | 70 | 0.406 (0.434) | 0.959 | 0.991 | 0 | 124.3 |
| minilm_only | 70 | 0.323 (0.434) | 0.827 | 0.835 | 0 | 118.0 |
| keyword_rules | 70 | 0.434 (0.434) | 0.995 | 1.000 | 0 | 0.3 |
| e5_rules | 70 | 0.434 (0.434) | 0.995 | 1.000 | 0 | 20.8 |
| minilm_rules | 70 | 0.434 (0.434) | 0.995 | 1.000 | 0 | 17.4 |

MRR by category:

| Category | Queries | keyword_raw | keyword | e5_only | minilm_only | keyword_rules | e5_rules | minilm_rules |
|---|---|---|---|---|---|---|---|---|
| exact | 10 | 1.000 | 1.000 | 1.000 | 0.933 | 1.000 | 1.000 | 1.000 |
| generic | 13 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 |
| brand_only | 7 | 1.000 | 1.000 | 1.000 | 0.795 | 1.000 | 1.000 | 1.000 |
| abbreviation | 6 | 0.732 | 1.000 | 1.000 | 0.783 | 1.000 | 1.000 | 1.000 |
| misspelling | 10 | 0.600 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 |
| bangla | 8 | 0.000 | 1.000 | 0.917 | 0.423 | 1.000 | 1.000 | 1.000 |
| banglish | 5 | 1.000 | 1.000 | 1.000 | 0.607 | 1.000 | 1.000 | 1.000 |
| strength | 4 | 0.812 | 1.000 | 1.000 | 0.781 | 1.000 | 1.000 | 1.000 |
| form | 3 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 |
| combination | 4 | 1.000 | 1.000 | 1.000 | 0.833 | 1.000 | 1.000 | 1.000 |

### Held-out set (30 phrases)

| Method | Queries | Precision@5 (best possible) | Recall@5 | MRR | No relevant result | ms / query |
|---|---|---|---|---|---|---|
| keyword_raw | 30 | 0.280 (0.447) | 0.684 | 0.705 | 6 | 0.1 |
| keyword | 30 | 0.393 (0.447) | 0.921 | 0.903 | 1 | 0.4 |
| e5_only | 30 | 0.420 (0.447) | 0.956 | 0.983 | 0 | 126.1 |
| minilm_only | 30 | 0.320 (0.447) | 0.727 | 0.713 | 0 | 115.8 |
| keyword_rules | 30 | 0.433 (0.447) | 0.961 | 0.925 | 1 | 0.3 |
| e5_rules | 30 | 0.447 (0.447) | 0.994 | 0.978 | 0 | 28.4 |
| minilm_rules | 30 | 0.427 (0.447) | 0.928 | 0.930 | 0 | 27.2 |

MRR by category:

| Category | Queries | keyword_raw | keyword | e5_only | minilm_only | keyword_rules | e5_rules | minilm_rules |
|---|---|---|---|---|---|---|---|---|
| misspelling | 5 | 0.700 | 1.000 | 1.000 | 0.767 | 1.000 | 1.000 | 1.000 |
| salt_name | 7 | 1.000 | 1.000 | 1.000 | 0.806 | 1.000 | 1.000 | 1.000 |
| label_text | 2 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 |
| unlisted_brand | 2 | 0.500 | 0.500 | 1.000 | 0.625 | 0.500 | 1.000 | 0.625 |
| form_word | 2 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 |
| quantity_mixed | 4 | 0.661 | 0.833 | 1.000 | 0.800 | 1.000 | 1.000 | 1.000 |
| bangla_unseen | 5 | 0.000 | 0.750 | 0.900 | 0.095 | 0.750 | 0.867 | 0.729 |
| other | 3 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 | 1.000 |

### A bug the held-out set exposed

The first held-out run showed that Bangla words **not** in the Bangla word list were cut apart ("আমলোডিপিন" became
"আমল"), because the character filter removed Bangla vowel signs. The fix keeps the whole Bangla Unicode block. It is a
general fix, not tuned to these phrases, but because the held-out set revealed it, the results before the fix are
kept in `evaluation/results/heldout-before-bangla-fix/`:

| Method (held-out, before the fix) | Precision@5 | Recall@5 | MRR |
|---|---|---|---|
| keyword | 0.393 | 0.921 | 0.903 |
| keyword_rules | 0.433 | 0.961 | 0.925 |
| e5_rules | 0.440 | 0.978 | 0.978 |
| minilm_rules | 0.433 | 0.944 | 0.946 |

The fix raised e5_rules' Recall@5 (0.978 → 0.994) and Precision@5 (0.440 → 0.447); its MRR stayed at 0.978.

### Conclusions

- **Model choice: multilingual-e5-small.** On the held-out set it beat paraphrase-multilingual-MiniLM in every
  configuration (MRR 0.978 vs 0.930 with rules; 0.983 vs 0.713 alone), and MiniLM handled Bangla badly on its own
  (Bangla MRR 0.095 on the held-out set).
- **The strict rules are what stop wrong medicines.** Without them, every method put a different medicine into the
  top 5 for "Napa 500 mg tablet": keyword and MiniLM added Napa Extra (paracetamol + caffeine) and Napa Extend (665 mg),
  and e5 alone added **Comet 500 mg, which is metformin**, sharing only the strength. With the rules the top 5 held
  only correct listings, and e5's Precision@5 reached the best possible value on both sets. This is why text
  similarity is never allowed to decide medicine identity on its own.
  On the held-out set e5 alone had a marginally higher MRR (0.983) than e5 with rules (0.978), which is within the
  noise of 30 phrases; the rules are kept because they guarantee identity, not because of that score.
- **The model adds most where the rules cannot identify a medicine**: brands missing from the catalogue ("ventolin",
  "ors packet") and Bangla spellings missing from the word list. Keyword ranking found nothing for "ors packet".
- **Remaining weakness**: "নাপা এক্সটেন্ড" (Napa Extend in Bangla) ranks Napa 500 mg first and the correct
  665 mg listing third, because "এক্সটেন্ড" is not recognised as "Extend". Adding it to the Bangla word list would fix
  this phrase, but must then be judged on new held-out phrases, not these.
- **Limits of this evaluation**: 31 listings and 100 phrases written by the project team. It shows the approach works
  and where it fails; it is not a measure of real-world accuracy.

## Tests

```bash
.venv/bin/python -m pytest
```
