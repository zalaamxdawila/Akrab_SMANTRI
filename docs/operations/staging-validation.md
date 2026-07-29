# Staging Validation and Release Candidate Gate

Use only a dedicated staging host and database. Never use `u602402025_akrab`.

## Build and deploy

1. Build a new immutable directory: `php tools/build_release.php <new-directory>`.
2. Verify `release-manifest.sha256`, archive the directory outside the web root, then upload it.
3. Configure staging secrets in the hosting control panel with `AKRAB_APP_ENV=staging`.
4. Run migrations with a least-privilege migration account.
5. Seed synthetic accounts using `AKRAB_STAGING_FIXTURE_PASSWORD` and:
   `php tools/seed_staging.php --confirm-synthetic-data`.
6. Confirm `/health.php` returns healthy without exposing credentials.

## Post-deployment evidence matrix

Record timestamp, operator, result, and evidence link for every row.

| Gate | Required evidence |
| --- | --- |
| Automated regression | `composer quality` output from deployed revision |
| Role isolation | Siswa, UKS, and orang tua authorization matrix |
| CSRF/session | Invalid token rejected; rotation, idle, and absolute timeout verified |
| Import/export | Valid and malformed CSV; formula injection neutralized |
| Migration | Apply twice; second run is a no-op |
| Security | No high/critical finding; headers and secret scan captured |
| Performance | Critical pages meet documented budget; query EXPLAIN captured |
| Accessibility | Keyboard, screen reader smoke, contrast, and Lighthouse/axe evidence |
| PWA | Offline shell, logout cache isolation, and update flow verified |
| Observability | Correlation ID, structured error, audit event, and health evidence |

## Go/no-go thresholds

GO requires all critical journeys passing and no unresolved high/critical issue.
HOLD applies to incomplete evidence or a material performance regression.
ROLLBACK applies to data-integrity/security defects, error rate above 2x baseline,
or p95 latency more than 50% above baseline.

The clinical feature stays OFF unless its separate activation prerequisites are
all satisfied. A release candidate is frozen only after CP-10 becomes GREEN.
