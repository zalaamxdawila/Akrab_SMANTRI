# Release Checklist and Ownership

## Ownership matrix

| Area | Accountable | Responsible | Approval/evidence |
|---|---|---|---|
| Product/UAT | Product owner | UAT coordinator | Written Go/No-Go |
| Data governance | Data owner | Application operator | Retention and deletion policy |
| Clinical model | Clinical owner | Model maintainer | CP-07 metadata and checksum |
| Security/privacy | Security owner | Reviewer | Secret scan, headers, role tests |
| Database migration | Data owner | Migration operator | Backup and rehearsal output |
| Deployment | Product owner | Deploy owner | CP-11 checklist |
| Rollback | Incident commander | Rollback owner | Previous release and restore path |
| Monitoring | Operations owner | On-call operator | Health/alert/log evidence |

Names and contact channels must be filled in the private operations register, not this repository.

## Pre-release

- [ ] Release commit/tag and checksum frozen.
- [ ] CP-08/09 runtime evidence resolved or formally accepted.
- [ ] Lint, unit, integration, browser, security, performance, and UAT evidence attached.
- [ ] Secret scan and package allowlist clean.
- [ ] Database migration rehearsed on a clone.
- [ ] Backup and restore drill PASS.
- [ ] Clinical flag/approval metadata verified.
- [ ] Alert routes, health endpoint, logs, and audit trail verified.
- [ ] Previous release, rollback owner, and deploy owner ready.

## Go/No-Go

- [ ] Product owner: GO.
- [ ] Data owner: GO.
- [ ] Clinical owner: GO or clinical feature confirmed OFF.
- [ ] Security owner: GO.
- [ ] Deploy and rollback owners acknowledge the window.

## Post-release

- [ ] Health and critical flows PASS.
- [ ] Migration/schema status current.
- [ ] Error rate and p95 within thresholds.
- [ ] CSP/PWA/browser evidence captured.
- [ ] Structured logs and audit records verified free of sensitive payloads.
- [ ] 60-minute observation completed; seven-day follow-up scheduled.
- [ ] Database credential shared previously rotated after stability window.
