# Production Readiness — `akrab.portodq.com`

This is an operator evidence sheet. Never paste passwords, tokens, database
dumps, or personally identifiable data into it.

## Hosting preflight

Record PASS/FAIL, timestamp, operator, and private evidence link:

| Check | Required state |
| --- | --- |
| DNS/HTTPS | `akrab.portodq.com` resolves correctly; valid certificate and forced HTTPS |
| Document root | Points only to the active versioned release |
| Runtime | PHP >= 8.1 with PDO MySQL, JSON, mbstring, and OpenSSL |
| Time | PHP and scheduled jobs use `Asia/Jakarta` |
| Storage | Quota has room for active, previous release, logs, and two backups |
| Permissions | Code read-only to web user; no public directory listing |
| Cron | Exact command, schedule, timezone, owner, and last-success evidence captured |
| Headers | CSP, HSTS, frame denial, nosniff, referrer and permissions policy present |

Run `php tools/preflight.php` after environment injection. Add
`--check-database` only from a trusted shell. The command reports presence and
status, never secret values.

## Database separation

- Production database is `u602402025_akrab`.
- Runtime user receives only `SELECT, INSERT, UPDATE, DELETE` on that database.
- Migration user is separate, temporary, and disabled or rotated after migration.
- Credentials live in the hosting secret/environment facility, never in the
  document root, release archive, Git, logs, shell history, or screenshots.
- The database password already shared in conversation must be injected only at
  deployment time and rotated after the release stabilizes.

Example grants for the hosting administrator, with account names and host
restrictions chosen in the control panel:

```sql
GRANT SELECT, INSERT, UPDATE, DELETE ON u602402025_akrab.* TO runtime_account;
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP
  ON u602402025_akrab.* TO migration_account;
```

Do not grant global privileges. Do not place actual usernames/passwords in this file.

## Backup and clone rehearsal

1. Enable the maintenance/read-only window.
2. Export files and database without a password in command arguments.
3. Encrypt off-host, create SHA-256 entries in a private backup manifest, then
   run `php tools/verify_backup.php <manifest>`.
4. Restore into an isolated non-production database and release directory.
5. Compare table counts and schema migrations; run migration twice and confirm
   the second pass is a no-op.
6. Run deployed-runtime validation against synthetic accounts only.
7. Record RPO, RTO, row-count differences, result, and cleanup confirmation.

## Release and rollback readiness

- Keep the last known-good package and checksum outside the document root.
- Upload the new release beside, not over, the active release.
- Make `maintenance.html` available to the hosting switch mechanism.
- Record the exact document-root/symlink switch and reversal commands privately.
- Roll back immediately on data-integrity/security failure, health failure twice,
  error rate above 2x baseline, or p95 latency more than 50% above baseline.

CP-11 becomes GREEN only after CP-10 is GREEN and every production-specific
item above has real evidence.
