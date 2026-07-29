# ADR-0001: Versioned Releases on PHP Shared Hosting

## Status

Accepted

## Date

2026-07-29

## Context

AKRAB must run on PHP shared hosting, handles sensitive health data, and cannot assume container orchestration or a managed deployment platform. Directly overwriting the live web root makes rollback slow and can mix files from different versions.

## Decision

Build releases from an explicit allowlist, upload them into versioned directories, inject secrets through hosting configuration, run versioned migrations with a separate DDL account, and switch the active release atomically when hosting permits. Keep the previous release available for file rollback. Database restores remain an explicitly approved incident operation.

## Alternatives considered

- Overwrite the live directory: rejected because partial uploads and rollback are unsafe.
- Commit `.env` into each release: rejected because credentials would enter source and packages.
- Container deployment: deferred because current hosting constraints do not guarantee container support.

## Consequences

- Deployment packages are reproducible and secret-free.
- Operators must maintain release directories, checksums, backup metadata, and an ownership register.
- Schema changes must be backward-compatible whenever rollback to the prior PHP release is expected.
