"""
Download the embedding model(s) into ai-service/models_cache before the first run.

    .venv/bin/python -m app.download_models          # the model the service uses (e5)
    .venv/bin/python -m app.download_models --all    # also the comparison model (for the evaluation)
"""

import sys

from app.config import settings
from app.ranking import semantic

if __name__ == "__main__":
    keys = list(semantic.MODELS) if "--all" in sys.argv else [settings.semantic_model]
    for key in keys:
        print(f"Loading {semantic.MODELS[key][0]} ...")
        semantic.load(key)
    print(f"Done. Models are in {settings.models_dir}")
