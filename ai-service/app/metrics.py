"""Ranking quality measures used in the evaluation."""


def precision_at_k(ranked: list[int], relevant: set[int], k: int = 5) -> float:
    """Share of the top k results that are relevant (missing positions count as not relevant)."""
    return sum(1 for item in ranked[:k] if item in relevant) / k


def recall_at_k(ranked: list[int], relevant: set[int], k: int = 5) -> float:
    """Share of all relevant listings that appear in the top k."""
    if not relevant:
        return 0.0
    return sum(1 for item in ranked[:k] if item in relevant) / len(relevant)


def reciprocal_rank(ranked: list[int], relevant: set[int]) -> float:
    """1 / position of the first relevant result; 0 when none is returned."""
    for position, item in enumerate(ranked, start=1):
        if item in relevant:
            return 1 / position
    return 0.0
