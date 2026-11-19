"""
Evaluate ranking methods on the hand-labelled dataset.

    cd ai-service
    .venv/bin/python -m evaluation.evaluate                # all methods
    .venv/bin/python -m evaluation.evaluate keyword_raw    # one method

Writes evaluation/results/<method>.json and prints a Markdown table.
Every query is ranked against the whole corpus, as if all listings were candidates.
"""

import json
import sys
from collections import defaultdict
from pathlib import Path
from statistics import mean

from app.metrics import precision_at_k, recall_at_k, reciprocal_rank
from app.ranking import keyword
from app.schemas import Candidate

HERE = Path(__file__).resolve().parent
DATASET = HERE / "dataset.json"
RESULTS = HERE / "results"
K = 5

METHODS = {
    "keyword_raw": lambda q, c: keyword.rank(q, c, normalized=False),
    "keyword": lambda q, c: keyword.rank(q, c, normalized=True),
}


def load():
    data = json.loads(DATASET.read_text(encoding="utf-8"))
    corpus = [Candidate(**item) for item in data["corpus"]]
    return data, corpus


def relevant_ids(intent: dict, corpus: list[Candidate]) -> set[int]:
    return {
        c.id for c in corpus
        if c.generic_name == intent["generic_name"]
        and (intent["strength"] is None or c.strength == intent["strength"])
        and (intent["dosage_form"] is None or c.dosage_form == intent["dosage_form"])
    }


def evaluate(method: str) -> dict:
    data, corpus = load()
    rank = METHODS[method]
    rows = []
    for q in data["queries"]:
        relevant = relevant_ids(q["intent"], corpus)
        assert relevant, f"Query {q['id']} has no relevant listing: check the dataset."
        ranked = [item_id for item_id, _ in rank(q["query"], corpus)]
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

    return {"method": method, "k": K, "overall": summary(rows),
            "by_category": {c: summary(items) for c, items in by_category.items()}, "queries": rows}


def markdown(results: list[dict]) -> str:
    lines = ["| Method | Queries | Precision@5 (best possible) | Recall@5 | MRR | Queries with no relevant result |", "|---|---|---|---|---|---|"]
    for r in results:
        o = r["overall"]
        lines.append(f"| {r['method']} | {o['queries']} | {o['precision_at_5']:.3f} ({o['best_possible_precision_at_5']:.3f}) | {o['recall_at_5']:.3f} | {o['mrr']:.3f} | {o['no_relevant_result']} |")
    categories = list(results[0]["by_category"])
    lines += ["", "MRR by category:", "", "| Category | Queries | " + " | ".join(r["method"] for r in results) + " |", "|---|---|" + "---|" * len(results)]
    for c in categories:
        lines.append(f"| {c} | {results[0]['by_category'][c]['queries']} | " + " | ".join(f"{r['by_category'][c]['mrr']:.3f}" for r in results) + " |")
    return "\n".join(lines)


if __name__ == "__main__":
    chosen = sys.argv[1:] or list(METHODS)
    RESULTS.mkdir(exist_ok=True)
    all_results = []
    for method in chosen:
        result = evaluate(method)
        (RESULTS / f"{method}.json").write_text(json.dumps(result, indent=1, ensure_ascii=False) + "\n", encoding="utf-8")
        all_results.append(result)
    print(markdown(all_results))
