# Sprint 22 Release Candidate Notes

Date: 2026-07-29

## Included

- Security, authorization, validation, and integrity hardening from Sprints 1–17.
- Pagination and performance baselines from Sprint 18.
- Structured observability and health checks from Sprint 19.
- PWA, frontend security, assistant language, and accessibility baselines from Sprint 20.
- Operational, recovery, and rollback runbooks from Sprint 21.
- Immutable allowlist-based release builder and fail-closed synthetic staging seeder.

## Known limitations

- Staging deployment, runtime regression, browser evidence, and three-role UAT
  require a dedicated staging host and remain pending.
- Clinical behavior remains fail-closed until every activation prerequisite is met.
- Shared hosting requires an operator-controlled version switch and rollback.

This document describes a candidate, not production approval. The authoritative
decision is CP-10.
