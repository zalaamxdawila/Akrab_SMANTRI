# Checklist Sprint AKRAB

Status: `[ ]` belum, `[-]` berjalan, `[x]` selesai, `[!]` diblokir.

## Sprint 0 — Baseline

- [x] Inisialisasi Git dan `.gitignore`
- [x] Inventaris endpoint, tabel, role, dan file publik
- [x] Simpan baseline lint/smoke/checksum
- [x] Tambahkan disclaimer dan feature flag klinis OFF
- [x] Lengkapi CP-00

## Sprint 1 — Secret

- [x] Buat environment config loader
- [x] Buat `.env.example` tanpa secret
- [x] Keluarkan seluruh credential dari source
- [x] Jalankan secret scan

## Sprint 2 — Web root

- [x] Keluarkan utility/debug dari web root
- [x] Buat allowlist paket deployment
- [x] Terapkan production error handler
- [x] Lengkapi CP-01

## Sprint 3 — Test harness

- [x] Tambahkan Composer dan PHPUnit
- [x] Buat database/fixtures test
- [x] Tambahkan lint/unit/integration scripts
- [x] Tambahkan CI dan secret scan

## Sprint 4 — Schema

- [x] Rekonsiliasi schema aktual
- [x] Buat migration runner
- [x] Pindahkan DDL runtime ke migration
- [-] Uji fresh/existing migration
- [x] Lengkapi CP-02

## Sprint 5 — Session

- [x] Secure/HttpOnly/SameSite cookies
- [x] Regenerasi session ID
- [x] Idle dan absolute timeout
- [-] Test session fixation/logout

## Sprint 6 — CSRF

- [x] Buat CSRF helper
- [x] Lindungi seluruh mutasi
- [x] Ubah mutasi GET menjadi POST
- [x] Terapkan Post/Redirect/Get
- [x] Lengkapi CP-03

## Sprint 7 — Authorization

- [x] Buat role/action matrix
- [x] Tambahkan ownership checks
- [x] Batasi privilege DB runtime
- [x] Test horizontal/vertical escalation

## Sprint 8 — Identity

- [x] Tutup registrasi UKS publik
- [x] Buat provisioning UKS
- [x] Buat verified parent-student link
- [x] Tambahkan approval dan audit
- [x] Lengkapi CP-04

## Sprint 9 — Validation

- [x] Validator field dan enum
- [x] Batas nilai kesehatan
- [x] Validasi skor server-side
- [x] Pisahkan normalization dan output encoding

## Sprint 10 — CSV

- [x] Validasi type/size/header/row
- [x] Batasi jumlah baris dan waktu
- [x] Transaksi batch
- [x] Mitigasi formula injection
- [x] Lengkapi CP-05

## Sprint 11 — Integritas TTD/haid

- [x] Unique konsumsi per hari
- [x] Idempotent TTD write
- [x] State machine menstruasi
- [x] Koreksi syarat sertifikat

## Sprint 12 — Query/kategori

- [x] Satukan kategori kanonis
- [x] Mapping saran eksplisit
- [x] Deterministic latest record
- [x] Rekonsiliasi dashboard/laporan
- [x] Lengkapi CP-06

## Sprint 13 — Clinical specification

- [-] Tetapkan clinical owner
- [-] Definisikan scope screening
- [-] Definisikan threshold dan rujukan
- [x] Buat model card draft

## Sprint 14 — Model pipeline

- [-] Versioning dataset/provenance
- [x] Train/validation/test split
- [x] Ukur metrik dan calibration
- [x] Simpan artefak/checksum

## Sprint 15 — Model integration

- [x] Buat `AnemiaRiskService`
- [x] Simpan model version pada hasil
- [x] Golden parity tests
- [x] Kill switch dan fail-closed
- [x] Lengkapi CP-07

## Sprint 16 — Refactor konsultasi

- [x] Bootstrap aplikasi
- [x] Repository/service/controller boundaries
- [ ] Shared layout
- [x] Refactor vertical slice konsultasi

## Sprint 17 — Core flow

- [x] Refactor kuesioner scoring/persistence boundary
- [x] Refactor dashboard queries into repository
- [x] Global error handler correlation context
- [x] Request correlation ID
- [ ] Lengkapi CP-08

## Sprint 18 — Performance

- [x] Pagination konsultasi UKS dan eliminasi N+1 balasan
- [x] Pagination data siswa UKS dengan filter tetap terjaga
- [x] Pagination artikel UKS dengan ownership scope
- [x] Pagination semua list operasional
- [x] Siapkan skrip `EXPLAIN` query kritis
- [ ] Jalankan `EXPLAIN` setelah deployment
- [ ] Tambahkan indeks hanya jika didukung bukti
- [ ] Benchmark P95 setelah deployment

## Sprint 19 — Observability

- [x] Structured/redacted logs
- [x] Audit trail untuk aksi sensitif
- [x] Health endpoint aman
- [x] Alert dan retention policy

## Sprint 20 — PWA/frontend

- [x] Cache versioning dan cleanup
- [x] Asset fingerprint/version stabil
- [x] CSP/security headers
- [x] Rebranding/disclaimer chatbot
- [x] Accessibility baseline
- [x] Lengkapi CP-09 (YELLOW menunggu browser evidence)

## Sprint 21 — Operations

- [x] README dan environment setup
- [x] Deploy/migration/rollback runbook
- [x] Backup/restore drill procedure (execution deferred)
- [x] Incident dan secret rotation runbook
- [x] Data retention dan correction/deletion process
- [x] Release checklist dan ownership matrix

## Sprint 22 — Staging/UAT

- [x] Siapkan builder deployment staging dan seeder data sintetis
- [x] Siapkan matriks full regression/security/performance pascadeploy
- [x] Siapkan skenario dan kontrak bukti UAT tiga role
- [x] Siapkan release candidate notes dan checksum builder
- [x] Lengkapi CP-10 sebagai YELLOW/NO-GO hingga bukti staging tersedia
- [ ] Deploy ke staging dedicated (akses staging belum tersedia)
- [ ] Jalankan pengujian pascadeploy dan UAT
- [ ] Freeze checksum final setelah CP-10 GREEN

## Sprint 23 — Production readiness

- [x] Siapkan preflight hosting, SSL, PHP, cron, quota, dan permission
- [x] Siapkan verifikator backup file/database dan restore drill
- [x] Dokumentasikan least-privilege `u602402025_akrab`
- [x] Dokumentasikan injeksi/rotasi DB password melalui hosting secret
- [x] Siapkan prosedur dry-run migration pada clone
- [x] Siapkan release lama, maintenance page, dan rollback
- [x] Lengkapi CP-11 sebagai YELLOW/NO-GO hingga bukti hosting tersedia
- [ ] Jalankan production preflight pada hosting
- [ ] Ambil backup dan lakukan restore/migration rehearsal
- [ ] Dapatkan persetujuan eksplisit GO pada CP-11

## Sprint 24 — Production deployment

- [x] Catat baseline HTTP/health dan konfigurasi hosting
- [x] Backup database dan catat checksum paket rollback
- [x] Upload release hanya ke `akrab.portodq.com`
- [x] Inject production secrets melalui `.env` yang dilindungi
- [x] Preflight, lint, migration, dan idempotent rerun
- [x] Aktifkan release final dan tombstone endpoint migrasi
- [x] Health, public route, PWA, secret denial, dan header smoke
- [x] Lengkapi CP-12 sebagai LIVE/YELLOW
- [ ] UAT browser manusia tiga role
- [ ] Selesaikan monitoring tujuh hari
- [ ] Rotasi password database dan SSH setelah stabil

## Global release blockers

- [ ] Tidak ada critical/high security finding
- [ ] Tidak ada secret pada source/paket/log
- [ ] Database restore drill berhasil
- [ ] Semua migration rehearsal berhasil
- [ ] Role/ownership tests lulus
- [ ] Clinical feature OFF kecuali CP-07 GREEN
- [ ] Rollback owner dan deploy owner tersedia
- [ ] Product owner memberi Go/No-Go tertulis

## Program Superadmin — Detailed Task Breakdown

Specification: `docs/specs/superadmin-master-dashboard.md`
Plan: Sprint 25–32 pada `tasks/plan.md`

Aturan eksekusi:

- Kerjakan berdasarkan dependency, satu task pada satu waktu.
- Tulis regression/security test sebelum implementation.
- Maksimal sekitar lima file per task; pecah lagi bila scope bertambah.
- Feature flag superadmin tetap OFF sampai CP-20 GO.
- Tidak ada deployment/migration produksi tanpa approval.
- Tidak menyentuh domain/project selain AKRAB.

### Sprint 25 — Identity and authorization foundation

- [!] **SA25.1 — Tambahkan schema singleton superadmin dan status akun**
  - Acceptance: role enum memuat `superadmin`; status akun tersedia; unique
    generated key menolak superadmin kedua; migration existing/fresh aman.
  - Verify: focused migration/schema test, idempotent rerun, MariaDB rehearsal.
  - Depends: none.
  - Files: `database/migrations/008_superadmin_identity.php`,
    `database/schema.sql`, `tests/Unit/SchemaSnapshotTest.php`,
    `tests/Unit/MigrationRunnerTest.php`.

- [x] **SA25.2 — Tambahkan policy role dan feature flag fail-closed**
  - Acceptance: permission superadmin eksplisit; role lama tidak berubah; flag
    default OFF; dashboard mapping tidak menerima role palsu.
  - Verify: authorization/feature-flag unit tests dan config bootstrap tests.
  - Depends: SA25.1.
  - Files: `config/authorization.php`, `config/environment.php`,
    `.env.example`, `tests/Unit/AuthorizationPolicyTest.php`,
    `tests/Unit/EnvironmentConfigTest.php`.

- [x] **SA25.3 — Buat provisioning CLI satu-superadmin**
  - Acceptance: CLI-only; password dari environment/secure prompt; tidak
    mencetak credential; idempotent recovery; menolak akun kedua.
  - Verify: success/failure integration tests dan known-secret scan.
  - Depends: SA25.1, SA25.2.
  - Files: `tools/provision_superadmin.php`,
    `app/Services/SuperadminProvisioningService.php`,
    `tests/Integration/SuperadminProvisioningTest.php`,
    `docs/operations/superadmin-runbook.md`.

- [x] **SA25.4 — Hubungkan login superadmin dengan gate OFF**
  - Acceptance: superadmin hanya dapat login saat flag ON; session
    diregenerasi; akun nonaktif ditolak; role lama tetap masuk seperti semula.
  - Verify: login/session characterization tests, lint, full auth regression.
  - Depends: SA25.2, SA25.3.
  - Files: `login.php`, `config/session.php`,
    `tests/Unit/SessionSecurityTest.php`,
    `tests/Unit/SuperadminAuthenticationTest.php`.

- [!] **SA25.5 — Tutup CP-13**
  - Acceptance: seluruh gate Sprint 25 lulus dan tidak ada perubahan produksi.
  - Verify: lint, unit/integration, migration rehearsal, diff/secret review.
  - Depends: SA25.1–SA25.4.
  - Files: `tasks/checkpoints/CP-13.md`, `tasks/todo.md`.

### Sprint 26 — Audit dua identitas dan security kernel Login As

- [!] **SA26.1 — Tambahkan schema impersonation session dan audit context**
  - Acceptance: session menyimpan superadmin/target/reason/expiry/status; audit
    membedakan authenticated/effective actor dan request ID.
  - Verify: fresh/existing/idempotent migration plus FK/index assertions.
  - Depends: CP-13.
  - Files: `database/migrations/009_impersonation_audit.php`,
    `database/schema.sql`, `tests/Unit/SchemaSnapshotTest.php`,
    `tests/Integration/ImpersonationMigrationTest.php`.

- [x] **SA26.2 — Modelkan authenticated dan effective actor**
  - Acceptance: server menghasilkan immutable actor context; client tidak dapat
    memasok actor/role; normal session tetap kompatibel.
  - Verify: forged-session, unknown-role, inactive-account unit tests.
  - Depends: SA26.1.
  - Files: `app/Security/ActorContext.php`,
    `app/Security/ActorContextResolver.php`, `config/authorization.php`,
    `tests/Unit/ActorContextTest.php`.

- [x] **SA26.3 — Implementasikan lifecycle start/end/expire**
  - Acceptance: step-up password; reason tervalidasi; expiry 15 menit; session
    ID diregenerasi; nested impersonation ditolak.
  - Verify: start/end/expire/session-fixation integration tests.
  - Depends: SA26.2.
  - Files: `app/Security/ImpersonationService.php`, `config/session.php`,
    `config/validation.php`,
    `tests/Integration/ImpersonationLifecycleTest.php`.

- [x] **SA26.4 — Terapkan deny policy aksi kritis**
  - Acceptance: credential, role/status, archive/delete, export massal,
    config, clinical, dan nested Login As selalu diblokir server-side.
  - Verify: allow/deny matrix and forged-route tests.
  - Depends: SA26.2, SA26.3.
  - Files: `app/Security/ImpersonationPolicy.php`,
    `config/authorization.php`, `helpers.php`,
    `tests/Unit/ImpersonationPolicyTest.php`.

- [x] **SA26.5 — Audit setiap mutasi selama Login As**
  - Acceptance: POST outcome mencatat dua actor, session, reason category,
    route, request ID; metadata tidak memuat PII/credential.
  - Verify: success/failure/403 audit integration and redaction tests.
  - Depends: SA26.1–SA26.4.
  - Files: `config/observability.php`,
    `app/Security/ImpersonationMutationAudit.php`,
    `tests/Unit/ObservabilityTest.php`,
    `tests/Integration/ImpersonationAuditTest.php`.

- [!] **SA26.6 — Tutup CP-14**
  - Acceptance: kernel aman tanpa UI publik dan flag tetap OFF.
  - Verify: auth/security suite, lint, migration rehearsal, review.
  - Depends: SA26.1–SA26.5.
  - Files: `tasks/checkpoints/CP-14.md`, `tasks/todo.md`.

### Sprint 27 — Read-only master dashboard

- [ ] **SA27.1 — Buat overview repository**
  - Acceptance: metrik akun/relasi/konsultasi/artikel/TTD/health memakai query
    bounded tanpa N+1 dan mengabaikan archived row.
  - Verify: repository integration tests dan EXPLAIN fixture.
  - Depends: CP-14.
  - Files: `app/Repositories/SuperadminOverviewRepository.php`,
    `tests/Integration/SuperadminOverviewRepositoryTest.php`,
    `deployment/explain_queries.sql`.

- [ ] **SA27.2 — Buat shell dashboard superadmin**
  - Acceptance: route guarded; layout accessible/responsive; status clinical
    read-only; feature flag enforced.
  - Verify: authorization, HTML structure, keyboard, responsive smoke.
  - Depends: SA27.1.
  - Files: `superadmin/dashboard.php`,
    `views/superadmin/layout.php`,
    `assets/css/superadmin.css`,
    `tests/Unit/SuperadminRouteGuardTest.php`.

- [ ] **SA27.3 — Buat daftar dan detail pengguna read-only**
  - Acceptance: search/filter/pagination; detail tidak memuat password hash;
    invalid ID aman; direct URL tetap guarded.
  - Verify: repository pagination, IDOR, output encoding tests.
  - Depends: SA27.2.
  - Files: `app/Repositories/SuperadminUserRepository.php`,
    `superadmin/users.php`, `superadmin/user_detail.php`,
    `tests/Integration/SuperadminUserReadTest.php`.

- [ ] **SA27.4 — Buat audit viewer read-only**
  - Acceptance: filter waktu/actor/effective actor/action/outcome/request ID;
    pagination; audit append-only.
  - Verify: filter/query tests, authorization, XSS encoding.
  - Depends: SA26.5, SA27.2.
  - Files: `app/Repositories/SuperadminAuditRepository.php`,
    `superadmin/audit.php`,
    `tests/Integration/SuperadminAuditReadTest.php`.

- [ ] **SA27.5 — Tutup CP-15**
  - Acceptance: read-only dashboard browser-clean dan role lama tetap tertutup.
  - Verify: Selenium/Chrome isolated profile, console/network, accessibility,
    lint, unit/integration, EXPLAIN.
  - Depends: SA27.1–SA27.4.
  - Files: `tests/browser/superadmin_readonly.py`,
    `tasks/checkpoints/CP-15.md`, `tasks/todo.md`.

### Sprint 28 — User and parent-link management

- [ ] **SA28.1 — Tambahkan archive metadata relasi**
  - Acceptance: parent link dapat diarsip/restore tanpa hard delete dan tanpa
    mengubah relasi existing.
  - Verify: migration fresh/existing/idempotent and aggregate assertions.
  - Depends: CP-15.
  - Files: `database/migrations/010_parent_link_archive.php`,
    `database/schema.sql`,
    `tests/Integration/ParentLinkArchiveMigrationTest.php`.

- [ ] **SA28.2 — Implementasikan create user**
  - Acceptance: hanya siswa/UKS/orang tua; validation kuat; password di-hash;
    username unik; audit dan PRG.
  - Verify: validation, duplicate, CSRF, transaction, audit tests.
  - Depends: CP-15.
  - Files: `app/Services/SuperadminUserService.php`,
    `superadmin/user_create.php`,
    `tests/Integration/SuperadminUserCreateTest.php`.

- [ ] **SA28.3 — Implementasikan koreksi/status/archive user**
  - Acceptance: allowlisted fields; reason wajib; role conversion akun berdata
    ditolak; superadmin sendiri tidak dapat diubah; tidak ada hard delete.
  - Verify: invariant, FK, IDOR, CSRF, audit, rollback tests.
  - Depends: SA28.2.
  - Files: `app/Services/SuperadminUserService.php`,
    `superadmin/user_edit.php`, `superadmin/user_status.php`,
    `tests/Integration/SuperadminUserLifecycleTest.php`.

- [ ] **SA28.4 — Buat parent-link master view**
  - Acceptance: filter status/parent/student; pagination; archived visible
    hanya melalui filter; detail tervalidasi.
  - Verify: query, pagination, IDOR, encoding tests.
  - Depends: SA28.1.
  - Files: `app/Repositories/SuperadminParentLinkRepository.php`,
    `superadmin/parent_links.php`,
    `tests/Integration/SuperadminParentLinkReadTest.php`.

- [ ] **SA28.5 — Implementasikan keputusan/koreksi/archive parent-link**
  - Acceptance: approve/reject/correct/archive transactional; role target
    diverifikasi; reason/audit wajib; Login As ditolak.
  - Verify: state transition, forged target, CSRF, audit tests.
  - Depends: SA28.3, SA28.4.
  - Files: `app/Services/SuperadminParentLinkService.php`,
    `superadmin/parent_link_action.php`,
    `tests/Integration/SuperadminParentLinkMutationTest.php`.

- [ ] **SA28.6 — Tutup CP-16**
  - Acceptance: account/link lifecycle UAT lulus tanpa hard delete.
  - Verify: full account regression, browser UAT, audit review.
  - Depends: SA28.1–SA28.5.
  - Files: `tests/browser/superadmin_accounts.py`,
    `tasks/checkpoints/CP-16.md`, `tasks/todo.md`.

### Sprint 29 — Student health master data

- [ ] **SA29.1 — Tambahkan correction/archive metadata kesehatan**
  - Acceptance: additive metadata untuk kuesioner, hasil existing, Hb, TTD,
    dan menstruasi; data/constraint lama tetap utuh.
  - Verify: migration clone, idempotent rerun, schema assertions.
  - Depends: CP-16.
  - Files: `database/migrations/011_health_record_governance.php`,
    `database/schema.sql`,
    `tests/Integration/HealthGovernanceMigrationTest.php`.

- [ ] **SA29.2 — Buat repository health master read**
  - Acceptance: search/filter/pagination; deterministic ordering; archived
    excluded by default; no N+1.
  - Verify: repository integration tests dan EXPLAIN.
  - Depends: SA29.1.
  - Files: `app/Repositories/SuperadminHealthRepository.php`,
    `superadmin/health_records.php`,
    `tests/Integration/SuperadminHealthReadTest.php`,
    `deployment/explain_queries.sql`.

- [ ] **SA29.3 — Koreksi/archive kuesioner dan hasil existing**
  - Acceptance: domain validation reused; before/after values tidak masuk audit
    mentah; clinical model tidak dijalankan.
  - Verify: validation, transaction rollback, audit redaction, clinical gate.
  - Depends: SA29.2.
  - Files: `app/Services/SuperadminHealthService.php`,
    `superadmin/health_questionnaire_action.php`,
    `tests/Integration/SuperadminQuestionnaireGovernanceTest.php`.

- [ ] **SA29.4 — Koreksi/archive Hb dan konsumsi TTD**
  - Acceptance: range/category/unique-date integrity dijaga; reason/audit wajib;
    Login As master actions ditolak.
  - Verify: boundary, duplicate date, category, rollback, audit tests.
  - Depends: SA29.3.
  - Files: `app/Services/SuperadminHealthService.php`,
    `superadmin/health_hb_ttd_action.php`,
    `tests/Integration/SuperadminHbTtdGovernanceTest.php`.

- [ ] **SA29.5 — Koreksi/archive menstruasi dan agregat**
  - Acceptance: one-active-cycle invariant; archived rows tidak dihitung;
    historical rows tetap tersedia.
  - Verify: state machine, aggregate consistency, transaction/audit tests.
  - Depends: SA29.4.
  - Files: `app/Services/SuperadminHealthService.php`,
    `superadmin/health_menstruation_action.php`,
    `app/Repositories/DashboardRepository.php`,
    `tests/Integration/SuperadminMenstruationGovernanceTest.php`.

- [ ] **SA29.6 — Tutup CP-17**
  - Acceptance: health UAT lulus dan clinical feature tetap OFF.
  - Verify: health/security/full regression, browser UAT, audit redaction.
  - Depends: SA29.1–SA29.5.
  - Files: `tests/browser/superadmin_health.py`,
    `tasks/checkpoints/CP-17.md`, `tasks/todo.md`.

### Sprint 30 — Consultation, education, and notification management

- [ ] **SA30.1 — Tambahkan governance metadata operasional**
  - Acceptance: consultation/reply/article/advice/notification records dapat
    dikoreksi/diarsip tanpa hard delete.
  - Verify: migration existing/fresh/idempotent plus FK checks.
  - Depends: CP-17.
  - Files: `database/migrations/012_operational_governance.php`,
    `database/schema.sql`,
    `tests/Integration/OperationalGovernanceMigrationTest.php`.

- [ ] **SA30.2 — Kelola konsultasi dan balasan**
  - Acceptance: state consistency; ownership history tidak hilang; archive dan
    correction reason/audit wajib.
  - Verify: transition, IDOR, transaction rollback, audit tests.
  - Depends: SA30.1.
  - Files: `app/Services/SuperadminConsultationService.php`,
    `superadmin/consultations.php`,
    `superadmin/consultation_action.php`,
    `tests/Integration/SuperadminConsultationTest.php`.

- [ ] **SA30.3 — Kelola artikel dan saran edukasi**
  - Acceptance: lintas UKS tetapi actor asli tercatat; content tervalidasi dan
    di-encode; archive tanpa delete.
  - Verify: XSS, ownership, validation, CSRF, audit tests.
  - Depends: SA30.1.
  - Files: `app/Services/SuperadminEducationService.php`,
    `superadmin/education.php`, `superadmin/education_action.php`,
    `tests/Integration/SuperadminEducationTest.php`.

- [ ] **SA30.4 — Kelola jadwal dan log notifikasi**
  - Acceptance: schedule correction mempertahankan enum/time rules; delivery
    log hanya field allowlisted; archive/audit tersedia.
  - Verify: validation, IDOR, transaction, audit tests.
  - Depends: SA30.1.
  - Files: `app/Services/SuperadminNotificationService.php`,
    `superadmin/notifications.php`, `superadmin/notification_action.php`,
    `tests/Integration/SuperadminNotificationTest.php`.

- [ ] **SA30.5 — Tutup CP-18**
  - Acceptance: seluruh list operasional terpaginate dan browser-clean.
  - Verify: unit/integration/security, EXPLAIN, browser UAT, audit review.
  - Depends: SA30.1–SA30.4.
  - Files: `tests/browser/superadmin_operations.py`,
    `tasks/checkpoints/CP-18.md`, `tasks/todo.md`.

### Sprint 31 — Login As UX and route coverage

- [ ] **SA31.1 — Buat target picker dan step-up form**
  - Acceptance: target terpaginate; hanya active application role; password
    tidak dilog; reason tervalidasi; CSRF/PRG.
  - Verify: re-auth success/failure/rate limit, target/IDOR, audit tests.
  - Depends: CP-18, SA26.3.
  - Files: `superadmin/login_as.php`,
    `app/Security/ImpersonationService.php`,
    `tests/Integration/LoginAsStartTest.php`.

- [ ] **SA31.2 — Buat banner dan endpoint kembali**
  - Acceptance: banner server-rendered, countdown, target, POST return button;
    expiry dan session regeneration terjaga.
  - Verify: rendering, CSRF, end/expiry, accessibility tests.
  - Depends: SA31.1.
  - Files: `views/partials/impersonation_banner.php`,
    `assets/css/impersonation.css`, `end_impersonation.php`,
    `tests/Unit/ImpersonationBannerTest.php`.

- [ ] **SA31.3 — Integrasikan banner ke halaman siswa inti**
  - Acceptance: dashboard, ID card, profil, kuesioner, konsultasi selalu
    menampilkan banner saat Login As.
  - Verify: route-coverage assertion dan browser navigation.
  - Depends: SA31.2.
  - Files: `siswa/dashboard.php`, `siswa/id_card.php`, `siswa/profil.php`,
    `siswa/kuesioner.php`, `siswa/konsultasi.php`.

- [ ] **SA31.4 — Integrasikan banner ke halaman siswa pendukung**
  - Acceptance: edukasi, artikel, hasil, gizi, sertifikat selalu ber-banner.
  - Verify: route-coverage assertion dan direct URL checks.
  - Depends: SA31.2.
  - Files: `siswa/edukasi.php`, `siswa/baca_artikel.php`,
    `siswa/hasil_deteksi.php`, `siswa/kalkulator_gizi.php`,
    `siswa/cetak_sertifikat.php`.

- [ ] **SA31.5 — Tutup route siswa tersisa dan blocked actions**
  - Acceptance: calendar export/header dan profil credential mutation mengikuti
    deny policy; tidak ada route siswa tanpa actor context.
  - Verify: export/profile blocked tests dan route inventory.
  - Depends: SA31.3, SA31.4.
  - Files: `siswa/export_calendar.php`, `app/Security/ImpersonationPolicy.php`,
    `tests/Unit/StudentImpersonationRouteCoverageTest.php`.

- [ ] **SA31.6 — Integrasikan banner ke halaman UKS batch A**
  - Acceptance: dashboard, data/detail siswa, scan QR, laporan ber-banner.
  - Verify: route coverage dan direct URL browser checks.
  - Depends: SA31.2.
  - Files: `uks/dashboard.php`, `uks/data_siswa.php`,
    `uks/detail_siswa.php`, `uks/scan_qr.php`,
    `uks/cetak_laporan_eksekutif.php`.

- [ ] **SA31.7 — Integrasikan banner ke halaman UKS batch B**
  - Acceptance: konsultasi, artikel, tautan, edukasi, rujukan ber-banner.
  - Verify: route coverage; allowed mutation/blocked master action tests.
  - Depends: SA31.2.
  - Files: `uks/jawab_konsultasi.php`, `uks/kelola_artikel.php`,
    `uks/kelola_tautan.php`, `uks/edukasi.php`,
    `uks/cetak_rujukan.php`.

- [ ] **SA31.8 — Tutup route UKS tersisa dan orang tua**
  - Acceptance: import/export/profil mengikuti deny policy; parent dashboard
    ber-banner; seluruh protected routes terinventaris.
  - Verify: import/export/profile block tests and parent journey.
  - Depends: SA31.6, SA31.7.
  - Files: `uks/import_siswa.php`, `uks/export_csv.php`, `uks/profil.php`,
    `orangtua/dashboard.php`,
    `tests/Unit/ImpersonationRouteCoverageTest.php`.

- [ ] **SA31.9 — Tutup CP-19**
  - Acceptance: Login As tiga role lulus allowed/blocked mutation matrix,
    expiry, back/refresh/direct URL, banner, return, dan audit trace.
  - Verify: Selenium/Chrome isolated profile, accessibility tree,
    console/network, full session/security regression.
  - Depends: SA31.1–SA31.8.
  - Files: `tests/browser/login_as_three_roles.py`,
    `docs/uat/superadmin-login-as.md`,
    `tasks/checkpoints/CP-19.md`, `tasks/todo.md`.

### Sprint 32 — Reports, hardening, UAT, and release

- [ ] **SA32.1 — Buat reports dan audited export**
  - Acceptance: aggregate terpaginate/filterable; mass export hanya session
    superadmin asli; confirmation/reason/audit; no secret/archived default.
  - Verify: authorization, Login As deny, formula injection, performance tests.
  - Depends: CP-19.
  - Files: `app/Repositories/SuperadminReportRepository.php`,
    `superadmin/reports.php`, `superadmin/export.php`,
    `tests/Integration/SuperadminReportExportTest.php`.

- [ ] **SA32.2 — Jalankan full hardening dan performance gate**
  - Acceptance: tidak ada critical/high reachable finding; auth/CSRF/IDOR/XSS/
    session/audit suites green; P95/EXPLAIN tercatat.
  - Verify: composer quality pada PHP lengkap, dependency audit, Python tests,
    browser suite, benchmark.
  - Depends: SA32.1.
  - Files: `docs/performance-budget.md`,
    `docs/operations/release-checklist.md`,
    `tasks/checkpoints/CP-20.md`.

- [ ] **SA32.3 — Rehearse migration, backup, and rollback**
  - Acceptance: fresh/existing/idempotent migration; backup readable; rollback
    flag OFF/package lama diuji; checksum artifact dicatat.
  - Verify: clone rehearsal, backup verification, rollback dry run.
  - Depends: SA32.2.
  - Files: `docs/operations/deployment-runbook.md`,
    `docs/operations/backup-restore.md`,
    `docs/releases/superadmin-release.md`.

- [ ] **SA32.4 — Human UAT superadmin dan Login As**
  - Acceptance: dashboard master dan tiga-role Login As ditandatangani; defect
    critical/high nol; evidence tanpa PII/credential.
  - Verify: approved UAT matrix, screenshots sanitized, audit request tracing.
  - Depends: SA32.3.
  - Files: `docs/uat/superadmin-master-dashboard.md`,
    `tasks/checkpoints/CP-20.md`.

- [ ] **SA32.5 — Deploy terkontrol hanya ke AKRAB**
  - Acceptance: exact domain/root guard; flag OFF saat deploy; health/migration/
    smoke lulus; enable setelah GO; rollback siap; domain lain tidak berubah.
  - Verify: Hostinger exact-domain evidence, HTTPS/header/health, login, error/
    latency/audit monitoring, post-deploy browser UAT.
  - Depends: SA32.4 dan explicit production GO.
  - Files: `docs/releases/superadmin-release.md`,
    `tasks/checkpoints/CP-20.md`, `tasks/todo.md`.
