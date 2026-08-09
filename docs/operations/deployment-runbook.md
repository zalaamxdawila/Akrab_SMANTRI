# AKRAB deployment runbook

1. Assert the exact domain `akrab.portodq.com` and deploy root only. Do not deploy AKRAB to another hostname; superadmin remains under `/superadmin/` on this domain.
2. Retain the previous release archives and verify the same-day SQL backup.
3. Build from a clean commit using `tools/build_release.php`.
4. Package `.env` with `AKRAB_SUPERADMIN_ENABLED=false` and all clinical flags
   false.
5. Deploy the package to the AKRAB subdomain only.
6. Run migrations through a short-lived token-protected endpoint, then replace
   that endpoint with a 404 tombstone.
7. Provision one immutable superadmin, enable the superadmin flag, and execute
   HTTPS, login, authorization, Login As, QR, and error-disclosure smoke tests.
8. On failure: set the flag off, redeploy the retained prior package, restore
   the verified SQL backup if schema/data recovery is required.
