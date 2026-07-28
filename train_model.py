"""Reproducible, non-PII model training/evaluation entrypoint.

This script deliberately does not promote a model. Promotion requires clinical
and security approval outside this repository.
"""

from __future__ import annotations

import csv
import hashlib
import json
import os
import sys
from pathlib import Path
from typing import Callable, Iterable

FEATURES = ["Gender", "Hemoglobin", "MCH", "MCHC", "MCV"]
TARGET = "Result"
FORBIDDEN_COLUMNS = {"nama", "name", "username", "email", "address", "alamat", "nisn", "id"}
SPLIT_SEED = "akrab-split-v1"


def dataset_checksum(path: str | Path) -> str:
    digest = hashlib.sha256()
    with open(path, "rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def load_dataset(path: str | Path) -> list[dict[str, str]]:
    with open(path, newline="", encoding="utf-8-sig") as handle:
        reader = csv.DictReader(handle)
        columns = reader.fieldnames or []
        missing = [column for column in FEATURES + [TARGET] if column not in columns]
        if missing:
            raise ValueError(f"Dataset missing required columns: {', '.join(missing)}")
        forbidden = [column for column in columns if column.lower() in FORBIDDEN_COLUMNS]
        if forbidden:
            raise ValueError(f"PII columns are not allowed: {', '.join(forbidden)}")
        rows = list(reader)
    if len(rows) < 10:
        raise ValueError("Dataset must contain at least 10 rows for a split.")
    return rows


def deterministic_split(rows: list[dict[str, str]]) -> dict[str, list[dict[str, str]]]:
    splits = {"train": [], "validation": [], "test": []}
    for row in rows:
        canonical = "|".join(row.get(column, "") for column in FEATURES + [TARGET])
        bucket = int(hashlib.sha256(f"{SPLIT_SEED}|{canonical}".encode()).hexdigest()[:8], 16) % 100
        key = "train" if bucket < 70 else "validation" if bucket < 85 else "test"
        splits[key].append(row)
    if not all(splits.values()):
        raise ValueError("Deterministic split produced an empty partition; provide more data.")
    return splits


def binary_label(value: str) -> int:
    normalized = value.strip().lower()
    if normalized in {"1", "true", "yes", "anemia", "positive", "positif"}:
        return 1
    if normalized in {"0", "false", "no", "normal", "negative", "negatif", "not anemia"}:
        return 0
    raise ValueError(f"Unsupported target label: {value}")


def classification_metrics(actual: Iterable[int], predicted: Iterable[int], probabilities: Iterable[float]) -> dict[str, float]:
    actual = list(actual)
    predicted = list(predicted)
    probabilities = list(probabilities)
    if not actual or len(actual) != len(predicted):
        raise ValueError("Metric inputs must have equal non-zero lengths.")
    tp = sum(a == 1 and p == 1 for a, p in zip(actual, predicted))
    tn = sum(a == 0 and p == 0 for a, p in zip(actual, predicted))
    fp = sum(a == 0 and p == 1 for a, p in zip(actual, predicted))
    fn = sum(a == 1 and p == 0 for a, p in zip(actual, predicted))
    sensitivity = tp / (tp + fn) if tp + fn else 0.0
    specificity = tn / (tn + fp) if tn + fp else 0.0
    precision = tp / (tp + fp) if tp + fp else 0.0
    accuracy = (tp + tn) / len(actual)
    brier = sum((probability - label) ** 2 for probability, label in zip(probabilities, actual)) / len(actual)
    return {
        "accuracy": round(accuracy, 6),
        "sensitivity": round(sensitivity, 6),
        "specificity": round(specificity, 6),
        "precision": round(precision, 6),
        "brier_score": round(brier, 6),
    }


def promotion_allowed(artifact: dict, min_sensitivity: float = 0.80, min_specificity: float = 0.80) -> bool:
    """Return whether governance gates permit promotion; never changes files."""
    metrics = artifact.get("metrics", {}).get("test", {})
    approvals = all(os.getenv(name, "false").lower() == "true" for name in (
        "CLINICAL_OWNER_APPROVED", "CLINICAL_MODEL_APPROVED", "SECURITY_MODEL_APPROVED"
    ))
    return approvals and float(metrics.get("sensitivity", 0)) >= min_sensitivity and float(metrics.get("specificity", 0)) >= min_specificity


def train_anemia_model(csv_path: str, artifact_path: str | None = None) -> dict:
    rows = load_dataset(csv_path)
    partitions = deterministic_split(rows)
    try:
        import pandas as pd
        from sklearn.linear_model import LogisticRegression
    except ImportError as exc:
        raise RuntimeError("Training requires pandas and scikit-learn; dataset audit remains available.") from exc

    train = partitions["train"]
    model = LogisticRegression(max_iter=1000, random_state=2024)
    model.fit(pd.DataFrame(train)[FEATURES], [binary_label(row[TARGET]) for row in train])
    metrics = {}
    for name in ("validation", "test"):
        subset = partitions[name]
        probabilities = model.predict_proba(pd.DataFrame(subset)[FEATURES])[:, 1].tolist()
        metrics[name] = classification_metrics(
            [binary_label(row[TARGET]) for row in subset],
            [int(probability >= 0.5) for probability in probabilities],
            probabilities,
        )

    artifact = {
        "model_version": "akrab-risk-v1",
        "dataset_sha256": dataset_checksum(csv_path),
        "features": FEATURES,
        "split_seed": SPLIT_SEED,
        "split_counts": {name: len(rows) for name, rows in partitions.items()},
        "metrics": metrics,
        "promoted": False,
        "approval_required": True,
    }
    if artifact_path:
        Path(artifact_path).write_text(json.dumps(artifact, indent=2) + "\n", encoding="utf-8")
    return artifact


if __name__ == "__main__":
    if len(sys.argv) < 2:
        raise SystemExit("Usage: python train_model.py DATASET.csv [ARTIFACT.json]")
    result = train_anemia_model(sys.argv[1], sys.argv[2] if len(sys.argv) > 2 else None)
    print(json.dumps(result, indent=2))
