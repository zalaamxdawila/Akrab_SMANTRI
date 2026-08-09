# Questionnaire results release checkpoint

Saved: 2026-08-10 (Asia/Jakarta)
Status: YELLOW (deployed and technically verified; clinical activation and
authenticated human-browser UAT remain external gates)
Source commit: `022991d9ebc5112fd99c487ed20001dd820d41db`

## Delivered

- Existing respondents become eligible again on 2026-08-17; a response on or
  after that cutoff starts a new six-month cooldown.
- Eligibility is rechecked under a database row lock so concurrent double
  submissions cannot bypass the cooldown.
- New submissions preserve a versioned allowlisted snapshot of visible answers
  without changing the clinical scoring inputs or thresholds.
- Shared result presentation provides an immediately visible summary and an
  expandable complete result, including four score aspects, priorities,
  actions, lab/menstruation/diet fields, exact visible questions and answers,
  historical-data fallback, and clinical disclaimer.
- Student access remains owner-only; parent access remains limited to an
  approved linked student; UKS and superadmin use their existing guarded,
  audited all-student access.

## Verification

- PHP lint: PASS.
- PHPUnit: PASS — 233 tests, 10,244 assertions.
- Python model regression: PASS — 4 tests.
- JavaScript syntax and `git diff --check`: PASS.
- Composer dependency audit: PASS — no known advisories.
- Release package: 174 allowlisted files, SHA-256
  `379ea90c90490cec6cba0cfd15cd574b33f83cc08071a2b86800dd7f1a62b5e5`.
- Production feature-file verification: all 9 required files match their
  release-manifest hashes.
- Production database: `answers_snapshot` exists exactly once; migration rerun
  reports schema current.
- Production preflight: PASS, including fail-closed clinical check.
- HTTPS: health 200, home 200, PWA guide PDF 200, `.env` 403, web migration
  entry point 404. Protected result routes reject/redirect anonymous access and
  do not return 500.
- No sibling project directory changed within the deployment window. The first
  helper alarm was a false positive caused by Hostinger's dynamic SSH login
  banner being included in the snapshot text.

## Production scope and rollback evidence

- Only application root:
  `/home/u602402025/domains/portodq.com/public_html/akrab`.
- Only database: `u602402025_akrab`.
- Pre-release application backup:
  `/home/u602402025/akrab-backups/akrab-pre-questionnaire-20260809_165458.tar.gz`
  (source commit `f8f2ee095bf6baf55966797096f81dd98611a937`, SHA-256
  `e9b1432b5708ef33ecbc0ad6dfd4d07e681612d0a8f79abfaa5c04f1fb342863`).
- Pre-release database backup:
  `/home/u602402025/akrab-backups/u602402025_akrab-pre-questionnaire-20260809_165458.sql.gz`
  (gzip integrity PASS, SHA-256
  `8c5181a1a9d3aee0daf3028d64441f8452b32715ed13bb49bc8eba1236bad35e`).
- Migration 015 is additive and nullable, so the retained old application can
  ignore the new column during an application-only rollback.

## Remaining external gates

- Production `CLINICAL_GATE` is OFF. The 17 August eligibility rule is deployed,
  but questionnaire submission remains fail-closed until the clinical owner,
  model, version, and checksum approvals are valid. This release did not invent
  or bypass those approvals.
- Chrome DevTools/browser automation was unavailable and no production test
  accounts were mutated. Authenticated human-browser UAT remains required; unit,
  integration, role-boundary, XSS/render, and anonymous production route tests
  provide the current automated evidence.
