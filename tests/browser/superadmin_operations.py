"""Fail-closed browser/security contract for Sprint 30 operations."""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]


def main() -> None:
    lists = ["consultations.php", "education.php", "notifications.php"]
    actions = [
        "consultation_action.php",
        "education_action.php",
        "notification_action.php",
    ]
    shared = (ROOT / "superadmin" / "operations_list.php").read_text(
        encoding="utf-8"
    )
    assert "csrfInput()" in shared
    assert "escape_output($record['summary'])" in shared
    assert "pagination" in shared
    for page in lists:
        source = (ROOT / "superadmin" / page).read_text(encoding="utf-8")
        assert "renderOperationsList" in source
    for page in actions:
        source = (ROOT / "superadmin" / page).read_text(encoding="utf-8")
        assert "SuperadminGuard::authorize" in source
        assert "REQUEST_METHOD" in source
        assert "requestCorrelationId()" in source
    print("Sprint 30 operations browser contracts: PASS")


if __name__ == "__main__":
    main()
