# AKRAB Observability Runbook

## On-call questions

1. Are users receiving elevated errors, and on which endpoint?
2. Did database connectivity fail or did application processing fail?
3. Which correlation ID links the user-visible failure to structured logs?
4. Which actor performed a sensitive action, on which target, and with what outcome?

## Signals

- Production requests emit JSON `http_request_completed` events containing a bounded route, method, status, duration, outcome, and correlation ID.
- Exceptions and database health failures emit structured error events without message text, request bodies, credentials, usernames, or health details.
- Sensitive business actions are stored in `audit_log`; detailed health values and article contents are never stored in audit metadata.
- `/health.php` returns only `{"status":"ok"}` or `{"status":"degraded"}` and uses `Cache-Control: no-store`.

## Alerts

Configure these in the hosting monitor/log platform during deployment:

| Severity | Symptom | Threshold | Duration | First action |
|---|---|---:|---:|---|
| page | Health endpoint unavailable/degraded | 2 consecutive failures | 2 minutes | Check database reachability and recent deployment |
| page | HTTP 5xx ratio | > 2% | 5 minutes | Filter `http_request_completed` by correlation ID and route |
| page | HTTP p95 latency | > 2 seconds | 10 minutes | Compare slow routes with database query plans |
| ticket | HTTP 4xx ratio | > 15% | 30 minutes | Check authentication/CSRF/user-flow regressions |
| ticket | Audit retention job missing | no success event | 48 hours | Verify cron and database permissions |

Disk/database resource thresholds remain hosting dashboards, not paging alerts, unless they cause the user-facing symptoms above.

## Retention and access

- Application JSON logs: retain 30 days, accessible only to the production operator and incident responder.
- Audit log: retain 365 days by default; change only with written data-governance approval.
- Run `php cron/purge_audit_log.php` daily. It deletes at most 5,000 expired rows per run.
- Never export production logs into the repository or support tickets without redaction.

## Deployment verification

1. Request `/health.php`; verify status code/body and absence of internal details.
2. Trigger one controlled staging error and locate it by `X-Request-ID`.
3. Complete login, export, article mutation, health-record view, and parent-link decision; verify audit records.
4. Confirm JSON logs contain no password, session ID, username, health measurement, or request body.
5. Test-fire every alert route and record delivery evidence.
