"""Static browser-contract smoke for Sprint 28.

Authenticated live-browser execution remains an environment UAT step while the
superadmin feature flag is off.
"""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]


def main() -> None:
    pages = {
        "user_create.php": ("csrfInput()", "autocomplete=\"new-password\""),
        "user_edit.php": ("csrfInput()", "reason"),
        "user_status.php": ("REQUEST_METHOD", "SuperadminGuard::authorize"),
        "parent_links.php": ("csrfInput()", "parent_link_action.php"),
        "parent_link_action.php": ("REQUEST_METHOD", "SuperadminGuard::authorize"),
    }
    for filename, markers in pages.items():
        source = (ROOT / "superadmin" / filename).read_text(encoding="utf-8")
        for marker in markers:
            assert marker in source, f"{filename}: missing {marker}"
    print("Sprint 28 browser contracts: PASS")


if __name__ == "__main__":
    main()
