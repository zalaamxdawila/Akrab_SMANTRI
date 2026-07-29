# Sprint 22 UAT — Three Roles

All names and records must be synthetic. Store screenshots and logs in the
private evidence repository, not in Git.

| Role | Critical journey | Expected result |
| --- | --- | --- |
| Siswa | Login, dashboard, questionnaire, consultation, logout | Own data only; safe language; session removed on logout |
| UKS | Login, student list, response, article management, import/export | Authorized actions succeed; invalid input is rejected safely |
| Orang tua | Login, request/link flow, view approved child summary, logout | No access before approval; only the linked student's allowed summary |

For each journey record: tester, timestamp, staging release ID, PASS/FAIL,
evidence link, defect ID, and retest result. Test denial cases by changing IDs
and roles; a redirect alone is insufficient evidence unless the data response
is also empty. CP-10 may be approved only when every critical row is PASS.
