"""Settings, read from environment variables (or ai-service/.env). Nothing secret is hard-coded."""

from pathlib import Path

from pydantic_settings import BaseSettings, SettingsConfigDict

BASE_DIR = Path(__file__).resolve().parent.parent


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_prefix="MEDIMATCH_AI_", env_file=BASE_DIR / ".env", extra="ignore")

    # Shared secret Laravel sends in the X-Api-Key header. Empty means no key is required (local development only).
    api_key: str = ""

    catalogue_path: Path = BASE_DIR / "data" / "catalogue.json"
    bangla_terms_path: Path = BASE_DIR / "data" / "bangla_terms.json"

    # Limits that protect the service from oversized requests
    max_candidates: int = 200
    max_query_length: int = 200


settings = Settings()
