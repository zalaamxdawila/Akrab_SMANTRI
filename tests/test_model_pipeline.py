import csv
import tempfile
import unittest
from pathlib import Path

import train_model


class ModelPipelineTest(unittest.TestCase):
    def test_split_is_deterministic_and_non_overlapping(self):
        rows = [
            {column: str(index if column != "Result" else index % 2)
             for column in train_model.FEATURES + [train_model.TARGET]}
            for index in range(60)
        ]
        first = train_model.deterministic_split(rows)
        second = train_model.deterministic_split(rows)
        self.assertEqual(first, second)
        self.assertEqual(sum(map(len, first.values())), 60)

    def test_pii_and_missing_columns_are_rejected(self):
        with tempfile.TemporaryDirectory() as directory:
            path = Path(directory) / "dataset.csv"
            path.write_text("Gender,Result,nama\n0,1,A\n", encoding="utf-8")
            with self.assertRaises(ValueError):
                train_model.load_dataset(path)

    def test_metrics_are_explicit_and_not_training_accuracy(self):
        metrics = train_model.classification_metrics([1, 0, 1, 0], [1, 0, 0, 0], [0.9, 0.1, 0.4, 0.2])
        self.assertEqual(metrics["sensitivity"], 0.5)
        self.assertEqual(metrics["specificity"], 1.0)
        self.assertIn("brier_score", metrics)

    def test_promotion_is_denied_without_approval_even_with_metrics(self):
        artifact = {"metrics": {"test": {"sensitivity": 1.0, "specificity": 1.0}}}
        self.assertFalse(train_model.promotion_allowed(artifact))


if __name__ == "__main__":
    unittest.main()
