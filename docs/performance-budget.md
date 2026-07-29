# AKRAB Performance Budget

Status: approved engineering baseline; runtime verification deferred to deployment.

## Server and database

- HTML endpoint p95: <= 500 ms on production hosting.
- Critical database query p95: <= 100 ms with representative production-like volume.
- Operational list page size: 10–25 rows; no unbounded user-facing list.
- Query count: no per-row/N+1 query in consultation lists.

## Browser

- LCP: <= 2.5 seconds on a representative mobile/4G profile.
- INP: <= 200 ms.
- CLS: <= 0.1.
- Initial JavaScript transferred: <= 200 KB gzipped, excluding explicitly documented third-party CDN assets.
- Initial CSS transferred: <= 50 KB gzipped, excluding explicitly documented third-party CDN assets.

## Deployment evidence required

1. Run `deployment/explain_queries.sql` against a production clone or production read-only session.
2. Record rows examined, access type, selected keys, and execution time.
3. Add indexes only for plans supported by that evidence and rehearse the migration first.
4. Capture five warm requests for each critical page and record p50/p95.
5. Capture Lighthouse or DevTools evidence for dashboard, student list, consultation, and questionnaire pages.

The sprint implementation may close without speculative indexes. The performance checkpoint cannot become GREEN until these measurements are attached.
