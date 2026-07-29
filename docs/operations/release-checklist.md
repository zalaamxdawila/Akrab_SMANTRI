# Superadmin release checklist

- [x] PHP lint passed.
- [x] PHPUnit passed: 172 tests, 2,398 assertions.
- [x] Browser contracts passed for accounts, health, operations, and Login As.
- [x] Clinical flags remain fail-closed.
- [x] Report export excludes secrets/archives and neutralizes formulas.
- [x] Exact production target is `https://akrab.portodq.com/`.
- [x] A same-day SQL backup and prior release packages are retained.
- [ ] Post-deploy migration and authenticated smoke tests (blocked by Hostinger
  pre-upload/API outage; no production write occurred).

Composer advisory retrieval is blocked by the locally installed legacy Composer
runtime on PHP 8.5; no package was added or upgraded by this release.
