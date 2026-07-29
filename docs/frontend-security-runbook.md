# Sprint 20 Frontend and PWA Runbook

## Implemented baseline

- Release asset version: `20260729`; bump it together with the service-worker cache name for every asset release.
- Service worker caches only same-origin static assets and the generic offline page.
- Authenticated navigation is network-only and never written to Cache Storage.
- Old cache versions are removed during activation.
- PHP responses send `private, no-store`; static assets receive long-lived cache headers.
- CDN packages use explicit versions. Migrating all third-party assets to self-hosted files remains preferred for a later release.
- The assistant is a local information helper, not a doctor, and includes escalation guidance.

## CSP note

The initial CSP still permits inline script/style because legacy pages contain many inline blocks. It restricts origins, frames, forms, objects, connections, workers, and images. Removing `'unsafe-inline'` requires nonce/hash migration and must be completed before calling the CSP strict.

## Deployment verification

1. Inspect response headers on public and authenticated pages.
2. Confirm authenticated HTML responses are never present in Cache Storage.
3. Install the PWA, deploy a bumped cache version, and confirm the old cache disappears.
4. Verify offline navigation displays only the generic offline page.
5. Keyboard-test login, questionnaire, consultation, assistant, pagination, and modal flows.
6. Run axe/Lighthouse and record WCAG, LCP, INP, CLS, and PWA evidence.
7. Verify pinned CDN assets load under CSP with no console violations.
