# Performance budget

- Server response P95 target: under 750 ms for authenticated list pages.
- Database list queries: at most 3 queries per request; all lists paginated.
- Superadmin page size: 25; hard maximum 100.
- CSV export: maximum 1,000 non-archived rows and no health data.
- Browser assets: no new runtime dependency was added in Sprint 32.

The reports implementation uses one bounded grouped count and one bounded page
query. Export is explicitly capped by `AKRAB_CSV_MAX_ROWS`.
