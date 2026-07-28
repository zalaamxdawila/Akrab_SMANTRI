# Reproducible Model Pipeline

`train_model.py` is an offline evaluation/training tool. It is not part of the web runtime and does not activate clinical screening.

## Contract

- Input must contain only the approved feature columns (`Gender`, `Hemoglobin`, `MCH`, `MCHC`, `MCV`) and `Result`.
- PII-like columns (`nama`, `username`, `email`, `alamat`, `nisn`, `id`, and equivalents) are rejected.
- Dataset checksum is recorded with SHA-256.
- Splitting uses a fixed versioned seed and canonical row hash, producing train/validation/test partitions.
- Evaluation reports sensitivity, specificity, precision, accuracy, and Brier score. Training accuracy is never used as the acceptance gate.
- Metrics and split counts are written to an artifact JSON with model version and dataset checksum.
- A model cannot be promoted without clinical owner, model, and security approvals plus minimum test metrics.

## Limitations

The current dataset has no subject/group identifier contract, so group-level leakage review must be completed when provenance is supplied. Thresholds are placeholders until the clinical owner signs the specification and model card. Do not place dataset files containing PII in this repository.

Example:

```text
python train_model.py approved_dataset.csv artifacts/model-v1.json
```
