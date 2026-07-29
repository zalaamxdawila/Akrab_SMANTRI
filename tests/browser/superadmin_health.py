"""Fail-closed browser contract for the Sprint 29 health master."""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]


def main() -> None:
    pages = [
        "health_records.php",
        "health_questionnaire_action.php",
        "health_hb_ttd_action.php",
        "health_menstruation_action.php",
    ]
    for page in pages:
        source = (ROOT / "superadmin" / page).read_text(encoding="utf-8")
        assert "SuperadminGuard::authorize" in source
        if page != "health_records.php":
            assert "REQUEST_METHOD" in source
            assert "requestCorrelationId()" in source
    listing = (ROOT / "superadmin" / "health_records.php").read_text(
        encoding="utf-8"
    )
    assert "csrfInput()" in listing
    assert "alamat" not in listing
    clinical = (ROOT / "config" / "clinical.php").read_text(encoding="utf-8")
    assert "'false'" in clinical
    print("Sprint 29 health browser contracts: PASS")


if __name__ == "__main__":
    main()
