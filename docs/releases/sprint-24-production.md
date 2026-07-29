# Sprint 24 Production Deployment Evidence

Date: 2026-07-29
Domain: `https://akrab.portodq.com/`
Hosting account: `u602402025`
Deployed source commit: `c5e5941`

## Deployment

- Hostinger mapping was filtered by exact domain before any write.
- Root resolved to `/home/u602402025/domains/portodq.com/public_html/akrab`.
- No file, cache, database, PHP setting, or cron belonging to another domain was changed.
- PHP 8.2 and required runtime extensions were confirmed.
- Production configuration is stored in protected `.env`; real secrets are not in Git.
- Clinical feature remains OFF.
- Database password was synchronized only for `u602402025_akrab`.
- Migrations 001–007 are recorded; rerun returned an empty applied list.
- The temporary migration endpoint was replaced by a 404 tombstone.
- PHP timezone, strict session mode, and OPcache updates were accepted for the Akrab domain.

## Artifacts

- Final release archive:
  `C:\tmp\akrab-s24-artifacts\akrab-s24-final_20260729_082800.zip`
- Final release SHA-256:
  `7FD6B79D8063AC5DEF1FF46E85BAA6B085D5F6DF7F5C4A7DB1548BE36E18707B`
- Post-migration database snapshot:
  `C:\tmp\akrab-production-backup-20260729\u602402025_akrab_postmigration_20260729_0845.sql`
- Snapshot SHA-256:
  `724E5E4A9132593F2FE60E424654D03775C4A8763D41903A2D136ACC50FC2C90`
- Previous local package SHA-256:
  `0BB077B56229D236DE9425D499045422DA38C455AE790B14A4FCA5CDC59079C2`

The initial pre-migration SQL export was verified before migration, but its local
filename was overwritten by a later phpMyAdmin export. The post-migration
snapshot above is the retained database artifact. This limitation prevents a
fully GREEN database rollback claim.

## Verification

- Health: HTTP 200, JSON `status=ok`.
- Public home, login, registration, service worker, and offline page: HTTP 200.
- `.env`: HTTP 403.
- Migration endpoint without and with its old token: HTTP 404 with zero body.
- HSTS, CSP, frame denial, nosniff, referrer, and permissions headers present.
- Secure, HttpOnly, SameSite=Lax session cookie observed.
- PHP lint passed.
- Python model suite passed: 4 tests.
- PHPUnit could not start on the local Windows PHP because `mbstring` is absent;
  Hostinger PHP reports `mbstring` enabled.
- Headless browser role smoke was blocked by Hostinger's browser challenge before
  reaching the login form. Human-browser UAT remains required.

## Stabilization follow-up

- Observe errors, latency, health, and audit events daily for seven days.
- Rotate database and SSH passwords after the stable window because they were
  shared through conversation.
- Complete human-browser login/UAT for siswa, UKS, and orang tua.
- Preserve the previous package until the rollback window closes.
