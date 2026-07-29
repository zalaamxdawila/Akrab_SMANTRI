# Backup and restore

The production database backup is exported only from `u602402025_akrab`.
Verify a non-zero SQL file and record SHA-256 before migration. Keep the backup
outside the web root.

Restore requires first disabling the superadmin flag, placing the application in
maintenance, importing the selected SQL file into `u602402025_akrab`, deploying
the matching prior release, running health checks, and only then reopening
traffic. Never import into another database or domain.
