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

- [ ] Structured/redacted logs
- [ ] Audit trail
- [ ] Health endpoint
- [ ] Alert dan retention policy

## Sprint 20 — PWA/frontend

- [ ] Cache versioning dan cleanup
- [ ] Asset fingerprint
- [ ] CSP/security headers
- [ ] Rebranding/disclaimer chatbot
- [ ] Accessibility audit
- [ ] Lengkapi CP-09

## Sprint 21 — Operations

- [ ] README dan environment setup
- [ ] Deploy/migration/rollback runbook
- [ ] Backup/restore drill
- [ ] Incident dan secret rotation runbook

## Sprint 22 — Staging/UAT

- [ ] Deploy staging dengan data sintetis
- [ ] Full regression/security/performance tests
- [ ] UAT tiga role
- [ ] Freeze release candidate
- [ ] Lengkapi CP-10

## Sprint 23 — Production readiness

- [ ] Verifikasi hosting, SSL, PHP, cron, permission
- [ ] Backup file dan database
- [ ] Configure `u602402025_akrab`
- [ ] Inject DB password sebagai secret, bukan source
- [ ] Dry-run migration pada clone
- [ ] Siapkan release lama dan maintenance mode
- [ ] Lengkapi CP-11

## Sprint 24 — Production deployment

- [ ] Catat baseline metrics
- [ ] Backup final
- [ ] Upload versioned release
- [ ] Inject production secrets
- [ ] Preflight, lint, dan migration
- [ ] Switch release
- [ ] Health dan smoke tests
- [ ] Monitor 60 menit dan 7 hari
- [ ] Rotasi password database setelah stabil
- [ ] Lengkapi CP-12

## Global release blockers

- [ ] Tidak ada critical/high security finding
- [ ] Tidak ada secret pada source/paket/log
- [ ] Database restore drill berhasil
- [ ] Semua migration rehearsal berhasil
- [ ] Role/ownership tests lulus
- [ ] Clinical feature OFF kecuali CP-07 GREEN
- [ ] Rollback owner dan deploy owner tersedia
- [ ] Product owner memberi Go/No-Go tertulis
