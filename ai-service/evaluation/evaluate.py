"""
Evaluate ranking methods on the hand-labelled phrases.

    cd ai-service
    .venv/bin/python -m evaluation.evaluate                      # all methods, development set (dataset.json)
    .venv/bin/python -m evaluation.evaluate --set heldout        # held-out set (heldout.json)
    .venv/bin/python -m evaluation.evaluate keyword e5_rules     # chosen methods

Writes evaluation/results/<set>/<method>.json and prints Markdown tables.
Every query is ranked against the whole corpus, as if all 31 listings were candidates.
"""

import json
import sys
import time
from collections import defaultdict
from pathlib import Path
from statistics import mean

from app.matching import semantic_query_text
from app.metrics import precision_at_k, recall_at_k, reciprocal_rank
from app.ranking import keyword, semantic
from app.rules import apply
from app.schemas import Candidate
from app.text import parse

HERE = Path(__file__).resolve().parent
DATASET = HERE / "dataset.json"
RESULTS = HERE / "results"
K = 5

def with_rules(ranker):
    """Apply the strict identity rules first, then rank what is left."""
    def run(query, corpus):
        parsed = parse(query)
        return ranker(query, parsed, apply(parsed, corpus).allowed)
    return run


METHODS = {
    # A1 baselines, no rules
    "keyword_raw": lambda q, c: keyword.rank(q, c, normalized=False),
    "keyword": lambda q, c: keyword.rank(q, c, normalized=True),
    # Embedding model alone, on the text exactly as typed, no rules
    "e5_only": lambda q, c: semantic.rank(q, c, "e5"),
    "minilm_only": lambda q, c: semantic.rank(q, c, "minilm"),
    # Rules first, then ranking (the A2 pipeline)
    "keyword_rules": with_rules(lambda q, p, allowed: keyword.rank(q, allowed, normalized=True)),
    "e5_rules": with_rules(lambda q, p, allowed: semantic.rank(semantic_query_text(p), allowed, "e5")),
    "minilm_rules": with_rules(lambda q, p, allowed: semantic.rank(semantic_query_text(p), allowed, "minilm")),
}

SETS = {"dev": HERE / "dataset.json", "heldout": HERE / "heldout.json"}


def load(which: str = "dev"):
    corpus = [Candidate(**item) for item in json.loads(DATASET.read_text(encoding="utf-8"))["corpus"]]
    queries = json.loads(SETS[which].read_text(encoding="utf-8"))["queries"]
    return queries, corpus


def relevant_ids(intent: dict, corpus: list[Candidate]) -> set[int]:
    return {
        c.id for c in corpus
        if c.generic_name == intent["generic_name"]
        and (intent["strength"] is None or c.strength == intent["strength"])
        and (intent["dosage_form"] is None or c.dosage_form == intent["dosage_form"])
    }


def evaluate(method: str, which: str = "dev") -> dict:
    queries, corpus = load(which)
    rank = METHODS[method]
    rank("warm up", corpus)  # load the model before timing
    rows, timings = [], []
    for q in queries:
        relevant = relevant_ids(q["intent"], corpus)
        assert relevant, f"Query {q['id']} has no relevant listing: check the dataset."
        started = time.perf_counter()
        ranked = [item_id for item_id, _ in rank(q["query"], corpus)]
        timings.append((time.perf_counter() - started) * 1000)
        rows.append({
            "id": q["id"], "category": q["category"], "query": q["query"],
            "relevant": sorted(relevant), "top5": ranked[:K],
            "p_at_5": precision_at_k(ranked, relevant, K),
            "r_at_5": recall_at_k(ranked, relevant, K),
            "rr": reciprocal_rank(ranked, relevant),
        })

    def summary(items):
        return {"queries": len(items),
                "precision_at_5": round(mean(r["p_at_5"] for r in items), 4),
                # A perfect ranking cannot score 1.0 when a query has fewer than 5 relevant listings
                "best_possible_precision_at_5": round(mean(min(len(r["relevant"]), K) / K for r in items), 4),
                "recall_at_5": round(mean(r["r_at_5"] for r in items), 4),
                "mrr": round(mean(r["rr"] for r in items), 4),
                "no_relevant_result": sum(1 for r in items if r["rr"] == 0)}

    by_category = defaultdict(list)
    for r in rows:
        by_category[r["category"]].append(r)

    return {"method": method, "set": which, "k": K, "overall": summary(rows),
            "milliseconds_per_query": round(mean(timings), 1),
            "by_category": {c: summary(items) for c, items in by_category.items()}, "queries": rows}


def markdown(results: list[dict]) -> str:
    lines = ["| Method | Queries | Precision@5 (best possible) | Recall@5 | MRR | No relevant result | ms / query |", "|---|---|---|---|---|---|---|"]
    for r in results:
        o = r["overall"]
        lines.append(f"| {r['method']} | {o['queries']} | {o['precision_at_5']:.3f} ({o['best_possible_precision_at_5']:.3f}) | {o['recall_at_5']:.3f} | {o['mrr']:.3f} | {o['no_relevant_result']} | {r['milliseconds_per_query']} |")
    categories = list(results[0]["by_category"])
    lines += ["", "MRR by category:", "", "| Category | Queries | " + " | ".join(r["method"] for r in results) + " |", "|---|---|" + "---|" * len(results)]
    for c in categories:
        lines.append(f"| {c} | {results[0]['by_category'][c]['queries']} | " + " | ".join(f"{r['by_category'][c]['mrr']:.3f}" for r in results) + " |")
    return "\n".join(lines)


if __name__ == "__main__":
    args = sys.argv[1:]
    which = "dev"
    if "--set" in args:
        which = args[args.index("--set") + 1]
        args = [a for a in args if a not in ("--set", which)]
    chosen = args or list(METHODS)

    out = RESULTS / which
    out.mkdir(parents=True, exist_ok=True)
    all_results = []
    for method in chosen:
        result = evaluate(method, which)
        (out / f"{method}.json").write_text(json.dumps(result, indent=1, ensure_ascii=False) + "\n", encoding="utf-8")
        all_results.append(result)
    print(f"Set: {which}\n")
    print(markdown(all_results))
