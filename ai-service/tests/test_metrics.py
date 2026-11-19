from app.metrics import precision_at_k, recall_at_k, reciprocal_rank


def test_precision_counts_missing_positions_as_not_relevant():
    assert precision_at_k([1, 2], {1, 2}, k=5) == 0.4
    assert precision_at_k([1, 9, 2, 8, 7], {1, 2}, k=5) == 0.4


def test_recall_is_the_share_of_relevant_items_found():
    assert recall_at_k([1, 9, 2], {1, 2, 3, 4}, k=5) == 0.5
    assert recall_at_k([], set(), k=5) == 0.0


def test_reciprocal_rank_uses_the_first_relevant_position():
    assert reciprocal_rank([9, 8, 1, 2], {1, 2}) == 1 / 3
    assert reciprocal_rank([9, 8], {1}) == 0.0
