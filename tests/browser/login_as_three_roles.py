"""Static contract for three-role Login As browser UAT."""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]


def main() -> None:
    picker = (ROOT / "superadmin" / "login_as.php").read_text(encoding="utf-8")
    banner = (ROOT / "views" / "partials" / "impersonation_banner.php").read_text(
        encoding="utf-8"
    )
    assert 'autocomplete="current-password"' in picker
    assert "reason_category" in picker and "reason_note" in picker
    assert "csrfInput()" in picker and "csrfInput()" in banner
    assert "end_impersonation.php" in banner
    assert "data-impersonation-countdown" in banner
    for role in ["siswa", "uks", "orangtua"]:
        assert role in (ROOT / "app" / "Security" / "ImpersonationService.php").read_text(
            encoding="utf-8"
        )
    print("Sprint 31 Login As three-role contracts: PASS")


if __name__ == "__main__":
    main()
