# Rencana Perbaikan dan Deployment AKRAB

## Ringkasan

Roadmap ini mengubah AKRAB dari prototipe PHP procedural menjadi aplikasi yang lebih aman, dapat diuji, memiliki skema database terkontrol, serta dapat dideploy secara reversible ke `https://akrab.portodq.com/`.

Rencana menggunakan 24 sprint berdurasi rekomendasi 3-5 hari kerja per sprint. Sprint boleh digabung jika kapasitas tim cukup, tetapi checkpoint tidak boleh dilewati. Data kesehatan dan keputusan klinis diperlakukan sebagai area berisiko tinggi.

## Batasan dan keputusan utama

- Tetap menggunakan PHP native + PDO agar kompatibel dengan shared hosting.
- Database produksi: `u602402025_akrab`.
- Password database tidak boleh ditulis di source, Git, dokumen, ZIP, log, atau screenshot. Nilainya disuntikkan sebagai secret saat deployment.
- Password yang telah dibagikan melalui percakapan dianggap sensitif dan direkomendasikan untuk dirotasi setelah deployment berhasil.
- Model risiko anemia tidak boleh diaktifkan untuk keputusan nyata sebelum lolos validasi klinis.
- Semua perubahan database menggunakan migrasi versioned, bukan DDL saat HTTP request.
- Setiap sprint harus menghasilkan perubahan kecil, test, bukti verifikasi, dan catatan rollback.

## Definition of Done setiap sprint

Sprint hanya dapat dinyatakan selesai jika:

1. Acceptance criteria sprint terpenuhi.
2. Seluruh PHP lolos `php -l`.
3. Test baru dan test regresi relevan lulus.
4. Tidak ada secret atau data pribadi pada diff/log.
5. Code review lima aspek selesai: correctness, readability, architecture, security, performance.
6. Dokumentasi dan migration note diperbarui bila relevan.
7. Rollback sprint telah didokumentasikan.
8. Bukti checkpoint disimpan pada `tasks/checkpoints/CP-XX.md`.

## Sistem checkpoint

Setiap checkpoint memiliki status `PENDING`, `GREEN`, `YELLOW`, atau `RED`.

- `GREEN`: seluruh gate lulus; sprint berikutnya boleh dimulai.
- `YELLOW`: ada deviasi terdokumentasi, pemilik risiko dan tenggat perbaikan; hanya pekerjaan non-produksi yang boleh lanjut.
- `RED`: blocker keamanan, integritas data, atau klinis; pekerjaan dependen dan deployment harus berhenti.

Template bukti checkpoint:

```markdown
# CP-XX — Nama Checkpoint
Status: PENDING
Tanggal:
Reviewer:
Commit/versi:

## Gate
- [ ] Acceptance criteria
- [ ] Lint/test/build
- [ ] Security review
- [ ] Data/migration review
- [ ] Manual smoke test
- [ ] Rollback tersedia

## Bukti
- Perintah dan hasil:
- Screenshot/rekaman:
- Temuan:
- Risiko tersisa:

## Keputusan
- GO / HOLD / ROLLBACK
- Penanggung jawab:
- Tindak lanjut:
```

Checkpoint utama:

| ID | Setelah sprint | Gate utama |
|---|---:|---|
| CP-00 | 0 | Baseline dan scope disetujui |
| CP-01 | 2 | Secret dan permukaan debug aman |
| CP-02 | 4 | Test harness dan schema baseline valid |
| CP-03 | 6 | Session, login, CSRF, dan mutasi aman |
| CP-04 | 8 | Role dan relasi orang tua-anak aman |
| CP-05 | 10 | Input/import/output aman |
| CP-06 | 12 | Integritas data dan query utama benar |
| CP-07 | 15 | Model klinis tervalidasi atau dinonaktifkan |
| CP-08 | 17 | Refactor fitur inti dan error handling stabil |
| CP-09 | 20 | Observability, PWA, dan UX lolos |
| CP-10 | 22 | Release candidate lulus staging/UAT |
| CP-11 | 23 | Infrastruktur produksi siap |
| CP-12 | 24 | Deployment dan observasi pascarilis lulus |

## Dependency graph

```text
Baseline
  -> Secret/config
  -> Test harness
  -> Schema/migrations
      -> Session/CSRF
      -> Authorization
      -> Validation
      -> Data integrity
          -> Clinical model
          -> Feature refactor
          -> Performance
              -> Observability
              -> Staging/UAT
                  -> Production preparation
                      -> Production deployment
```

## Sprint 0 — Baseline, inventaris, dan freeze risiko

**Tujuan:** Membentuk baseline yang dapat dibandingkan dan mencegah penggunaan hasil klinis yang belum valid.

**Pekerjaan:**

- Buat repository Git, `.gitignore`, branch policy, dan release naming.
- Catat checksum ZIP/source awal dan inventaris file publik.
- Buat daftar endpoint, role, tabel, alur data, dan trust boundary.
- Pasang banner bahwa hasil deteksi adalah prototipe/simulasi.
- Buat feature flag `CLINICAL_RISK_ENABLED=false`.

**Acceptance criteria:**

- Baseline lint dan smoke test terdokumentasi.
- Tidak ada perubahan klinis aktif tanpa feature flag.
- Threat model STRIDE awal tersedia.

**Verifikasi:** lint seluruh PHP, audit endpoint manual, dan pemeriksaan flag OFF.

**Rollback:** kembalikan ke arsip baseline terverifikasi.

**Checkpoint CP-00:** scope, pemilik sistem, dan klasifikasi data disetujui.

## Sprint 1 — Manajemen konfigurasi dan secret

**Tujuan:** Menghilangkan kredensial serta secret dari source.

**Pekerjaan:**

- Buat loader konfigurasi berbasis environment/server config.
- Tambahkan `.env.example` tanpa nilai rahasia.
- Pindahkan `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `BASE_URL`, dan secret aplikasi.
- Tambahkan validasi fail-fast untuk environment variable wajib.
- Scan source dan artefak ZIP untuk secret.

**Acceptance criteria:**

- Aplikasi berjalan dengan konfigurasi lokal di luar source.
- Tidak ada password database atau kode UKS di source/ZIP.
- Error konfigurasi tidak menampilkan nilai secret.

**Verifikasi:** secret scan, bootstrap test dengan config valid/tidak valid.

**Rollback:** gunakan konfigurasi lokal yang dibackup, bukan mengembalikan secret ke source.

## Sprint 2 — Pembersihan web root dan error disclosure

**Tujuan:** Menghapus permukaan serangan dari utility/debug.

**Pekerjaan:**

- Keluarkan `debug_export.php`, `test_export.php`, `inject.php`, `fix_paths.php`, dan `zip_project.php` dari web root produksi.
- Terapkan generic error page.
- Matikan `display_errors` pada produksi; arahkan detail ke log.
- Buat allowlist file deployment.
- Pastikan arsip, SQL, PDF internal, dan file konfigurasi tidak dapat diunduh bila tidak ditujukan publik.

**Acceptance criteria:**

- Endpoint utility memberi 404/403 di konfigurasi produksi.
- Stack trace, path, query, dan kredensial tidak muncul ke client.
- Deployment package hanya berisi file allowlist.

**Verifikasi:** HTTP negative tests dan inspeksi paket.

**Checkpoint CP-01:** secret scan bersih dan permukaan debug tertutup.

## Sprint 3 — Test harness dan quality automation

**Tujuan:** Menyediakan bukti regresi otomatis.

**Pekerjaan:**

- Tambahkan Composer dan PHPUnit.
- Buat bootstrap test dengan database khusus test.
- Tambahkan script lint, unit, integration, dan security scan.
- Tambahkan CI yang menjalankan quality gates.
- Buat fixtures untuk siswa, UKS, orang tua, kuesioner, dan hasil.

**Acceptance criteria:**

- Satu perintah menjalankan lint dan seluruh test.
- Test tidak pernah memakai database produksi.
- CI gagal jika lint/test/secret scan gagal.

**Verifikasi:** sengaja buat test gagal lalu pastikan CI memblokir.

## Sprint 4 — Baseline schema dan migration runner

**Tujuan:** Membuat skema database sebagai sumber kebenaran.

**Pekerjaan:**

- Rekonsiliasi `schema.sql` dengan tabel/kolom runtime.
- Buat tabel `schema_migrations`.
- Buat migrasi idempotent dan mekanisme status.
- Hapus DDL dari request registrasi/dashboard/export/artikel.
- Tambahkan backup dan rollback note per migrasi.

**Acceptance criteria:**

- Database kosong dapat dibangun penuh hanya melalui migrasi.
- Menjalankan migrasi dua kali tidak merusak data.
- Aplikasi runtime tidak memerlukan hak `CREATE`/`ALTER`.

**Verifikasi:** migrate fresh, migrate existing clone, dan schema diff.

**Checkpoint CP-02:** test harness hijau dan skema hasil migrasi cocok dengan spesifikasi.

## Sprint 5 — Session hardening

**Tujuan:** Melindungi sesi pengguna.

**Pekerjaan:**

- Atur cookie `Secure`, `HttpOnly`, `SameSite=Lax`, dan domain/path tepat.
- Regenerasi session ID setelah login dan perubahan privilege.
- Terapkan idle timeout dan absolute timeout.
- Hapus sesi secara lengkap saat logout.
- Tambahkan session security test.

**Acceptance criteria:**

- Session fixation test gagal mengeksploitasi aplikasi.
- Cookie hanya dikirim melalui HTTPS.
- Sesi kadaluarsa sesuai kebijakan.

**Verifikasi:** integration test dan inspeksi Set-Cookie.

## Sprint 6 — CSRF dan keamanan mutasi

**Tujuan:** Melindungi seluruh perubahan state.

**Pekerjaan:**

- Buat CSRF helper dengan token per sesi.
- Tambahkan token ke seluruh form mutasi.
- Ubah pencatatan TTD dan hapus artikel dari GET menjadi POST.
- Terapkan Post/Redirect/Get.
- Tambahkan test CSRF valid, hilang, dan salah.

**Acceptance criteria:**

- Semua mutasi tanpa token valid ditolak.
- GET bersifat read-only.
- Refresh tidak mengulang mutasi.

**Verifikasi:** daftar seluruh handler POST dan negative CSRF test.

**Checkpoint CP-03:** login, cookie, logout, CSRF, dan mutasi lulus security review.

## Sprint 7 — Authorization matrix dan least privilege

**Tujuan:** Memastikan setiap role hanya mengakses resource yang diizinkan.

**Pekerjaan:**

- Definisikan authorization matrix per endpoint/action.
- Pisahkan `check_login`, `require_role`, dan ownership check.
- Lindungi export, detail siswa, konsultasi, laporan, dan resource berbasis ID.
- Batasi database user runtime ke DML minimum.
- Tambahkan test horizontal dan vertical privilege escalation.

**Acceptance criteria:**

- Siswa tidak dapat membaca data siswa lain.
- Orang tua hanya dapat membaca anak terverifikasi.
- UKS hanya mendapat aksi yang tercantum pada matrix.

**Verifikasi:** automated role traversal tests.

## Sprint 8 — Registrasi aman dan relasi orang tua-anak

**Tujuan:** Menghilangkan self-registration privilege dan tautan anak yang lemah.

**Pekerjaan:**

- Tutup registrasi UKS publik.
- Buat provisioning UKS oleh admin/operator terotorisasi.
- Buat tabel relasi `parent_student_links` dengan status persetujuan.
- Tambahkan workflow permintaan dan approval UKS.
- Rate-limit registrasi dan cegah enumerasi NISN.

**Acceptance criteria:**

- Kode statis UKS tidak lagi digunakan.
- Mengetahui NISN saja tidak memberi akses data kesehatan.
- Setiap approval tercatat di audit log.

**Verifikasi:** abuse-case tests pendaftaran dan parent linking.

**Checkpoint CP-04:** matrix akses dan workflow identity disetujui pemilik data.

## Sprint 9 — Validasi input domain

**Tujuan:** Menjamin data yang masuk memenuhi kontrak teknis dan medis.

**Pekerjaan:**

- Buat validator reusable untuk identitas, password, tanggal, kelas, dan kuesioner.
- Whitelist enum dan tetapkan min/max numerik.
- Tolak payload checkbox/skor yang dimanipulasi.
- Terapkan batas panjang semua text field.
- Pisahkan normalisasi input dari HTML output encoding.

**Acceptance criteria:**

- Nilai di luar domain ditolak dengan pesan field-level.
- Role dan kategori tak dikenal tidak dapat disimpan.
- Data tersimpan tidak mengalami double encoding.

**Verifikasi:** unit boundary tests dan malicious payload tests.

## Sprint 10 — Hardening CSV import/export

**Tujuan:** Mengamankan batch import dan spreadsheet export.

**Pekerjaan:**

- Batasi ukuran file, jumlah baris, encoding, MIME/magic signature, dan waktu proses.
- Validasi header dan setiap row.
- Gunakan transaksi batch dan laporan error per baris.
- Mitigasi formula injection pada export.
- Tambahkan idempotency/import batch ID.

**Acceptance criteria:**

- File oversized/invalid ditolak.
- Import parsial tidak meninggalkan data inkonsisten.
- Nilai berawalan formula tidak dieksekusi saat CSV dibuka.

**Verifikasi:** test file valid, rusak, besar, formula, dan duplikat.

**Checkpoint CP-05:** seluruh boundary input/output lulus abuse-case review.

## Sprint 11 — Integritas konsumsi TTD dan menstruasi

**Tujuan:** Mencegah duplikasi dan manipulasi statistik.

**Pekerjaan:**

- Tambahkan unique constraint konsumsi `(user_id, tanggal)`.
- Jadikan pencatatan TTD idempoten.
- Definisikan state machine riwayat menstruasi.
- Cegah dua periode aktif bersamaan.
- Koreksi aturan kelayakan sertifikat menggunakan hari unik.

**Acceptance criteria:**

- Klik/request berulang tetap menghasilkan satu konsumsi per hari.
- Periode menstruasi tidak overlap secara ilegal.
- Statistik dan sertifikat memakai data unik yang benar.

**Verifikasi:** concurrency/idempotency integration tests.

## Sprint 12 — Konsistensi query dan kategori

**Tujuan:** Menghilangkan hasil ambigu dan mismatch kategori.

**Pekerjaan:**

- Definisikan satu enum/domain kategori kanonis.
- Buat mapping risiko ke saran yang eksplisit.
- Tentukan latest record dengan `(tanggal, id)`.
- Audit seluruh query agregasi dashboard/laporan.
- Tambahkan referential dan check constraints yang didukung.

**Acceptance criteria:**

- Setiap kategori hasil selalu memiliki saran valid.
- Dua hasil di tanggal sama dipilih secara deterministik.
- Dashboard dan detail menunjukkan nilai yang sama.

**Verifikasi:** fixture multi-record dan reconciliation tests.

**Checkpoint CP-06:** data integrity suite hijau dan laporan direkonsiliasi.

## Sprint 13 — Spesifikasi klinis dan tata kelola model

**Tujuan:** Menetapkan apa yang boleh diklaim oleh sistem.

**Pekerjaan:**

- Libatkan tenaga kesehatan sebagai clinical owner.
- Definisikan populasi, tujuan screening, label, threshold, dan kontraindikasi.
- Pisahkan “screening risk” dari diagnosis.
- Tetapkan disclaimer, jalur rujukan, dan emergency language.
- Buat model card dan approval record.

**Acceptance criteria:**

- Spesifikasi klinis ditandatangani clinical owner.
- UI tidak mengklaim diagnosis.
- Kondisi berbahaya selalu memberi instruksi eskalasi.

**Verifikasi:** clinical content review.

## Sprint 14 — Training pipeline dan validasi model

**Tujuan:** Menghasilkan model reproducible dan terukur.

**Pekerjaan:**

- Versioning dataset dan provenance tanpa memasukkan PII.
- Split train/validation/test dengan pencegahan leakage.
- Ukur sensitivity, specificity, precision, recall, ROC-AUC, calibration, dan subgroup behavior.
- Simpan koefisien/version/checksum sebagai artefak.
- Tetapkan acceptance threshold bersama clinical owner.

**Acceptance criteria:**

- Training reproducible dari versi dataset yang sama.
- Tidak memakai training accuracy sebagai bukti utama.
- Model yang gagal threshold tidak dapat dipromosikan.

**Verifikasi:** rerun pipeline dan independent metric review.

## Sprint 15 — Implementasi model aman dan fallback

**Tujuan:** Mengintegrasikan model tervalidasi tanpa silent failure.

**Pekerjaan:**

- Implementasikan `AnemiaRiskService`.
- Catat versi model pada setiap hasil.
- Tambahkan test parity Python vs PHP pada golden dataset.
- Tambahkan range check dan fail-closed behavior.
- Pertahankan feature flag/kill switch klinis.

**Acceptance criteria:**

- Selisih output Python-PHP berada dalam toleransi yang disetujui.
- Input Hb ekstrem menghasilkan respons aman dan teruji.
- Model bisa dimatikan tanpa deployment.

**Verifikasi:** golden tests, boundary tests, dan flag OFF/ON tests.

**Checkpoint CP-07:** wajib `GREEN` dari clinical owner dan security reviewer; jika tidak, feature tetap OFF.

## Sprint 16 — Pemisahan business logic dan template

**Tujuan:** Menurunkan kompleksitas file halaman.

**Pekerjaan:**

- Buat bootstrap aplikasi tunggal.
- Pisahkan repository, service, validator, dan controller sederhana.
- Ekstrak layout/nav/flash message.
- Refactor satu vertical slice lebih dahulu: konsultasi.
- Pertahankan URL lama atau sediakan redirect kompatibel.

**Acceptance criteria:**

- Business rule dapat dites tanpa rendering HTML.
- Tidak ada perubahan perilaku pada flow konsultasi.
- File baru tetap kecil dan memiliki tanggung jawab tunggal.

**Verifikasi:** regression tests dan manual flow comparison.

## Sprint 17 — Refactor dashboard, kuesioner, dan error handling

**Tujuan:** Menstabilkan alur paling penting.

**Pekerjaan:**

- Ekstrak scoring, persistence, dan rendering kuesioner.
- Pisahkan query dashboard ke repository.
- Buat exception taxonomy dan global handler.
- Tambahkan correlation/request ID.
- Terapkan transaksi pada multi-write.

**Acceptance criteria:**

- Kuesioner tidak menyimpan hasil parsial.
- Client mendapat pesan aman; log mendapat konteks non-PII.
- Critical flow lulus integration test.

**Verifikasi:** fault injection database dan end-to-end tests.

**Checkpoint CP-08:** core flow stabil dan error path teruji.

## Sprint 18 — Optimasi database dan pagination

**Tujuan:** Menjaga performa saat volume data meningkat.

**Pekerjaan:**

- Tambahkan pagination daftar siswa/konsultasi/artikel.
- Ambil `EXPLAIN` query kritis dengan dataset realistis.
- Tambahkan indeks berdasarkan bukti.
- Hilangkan subquery berkorelasi yang mahal.
- Tetapkan performance budget.

**Acceptance criteria:**

- Tidak ada list tanpa batas.
- Query kritis memakai indeks yang sesuai.
- P95 staging memenuhi budget yang disetujui.

**Verifikasi:** benchmark sebelum/sesudah dan `EXPLAIN`.

## Sprint 19 — Logging, audit trail, monitoring, dan health check

**Tujuan:** Membuat perilaku produksi dapat diamati.

**Pekerjaan:**

- Tambahkan structured logging dengan redaksi PII/secret.
- Audit login, approval relasi, akses data kesehatan, export, dan perubahan artikel.
- Buat `/health` yang memeriksa aplikasi dan koneksi database secara aman.
- Definisikan alert error rate, latency, disk, dan database failure.
- Tetapkan retention log dan akses operator.

**Acceptance criteria:**

- Tidak ada password, session ID, atau data kesehatan rinci dalam log.
- Security-sensitive action memiliki actor, action, target, timestamp, dan outcome.
- Health check tidak membocorkan detail internal.

**Verifikasi:** log inspection, redaction tests, dan simulated failure.

## Sprint 20 — PWA, frontend security, aksesibilitas, dan chatbot

**Tujuan:** Memperbaiki keamanan serta pengalaman frontend.

**Pekerjaan:**

- Perbaiki cache versioning, activation cleanup, offline fallback, dan update strategy.
- Hapus `?v=time()` dan gunakan asset fingerprint/version stabil.
- Self-host atau pin CDN dengan integrity bila memungkinkan.
- Terapkan CSP dan security headers.
- Ubah “Dokter AI” menjadi asisten informasi dengan disclaimer dan emergency guidance.
- Audit WCAG 2.1 AA, keyboard, focus, label, dan contrast.

**Acceptance criteria:**

- Data private tidak tersimpan tidak sengaja dalam cache publik.
- Service worker dapat di-update tanpa cache buntu.
- Chatbot tidak mengklaim diagnosis atau dokter.
- Critical flow dapat digunakan dengan keyboard.

**Verifikasi:** browser test, Lighthouse/axe, header inspection, offline/update test.

**Checkpoint CP-09:** observability, frontend security, PWA, dan accessibility hijau.

## Sprint 21 — Dokumentasi operasi dan disaster recovery

**Tujuan:** Membuat sistem dapat dioperasikan tanpa mengandalkan ingatan satu orang.

**Pekerjaan:**

- Buat README setup lokal/staging/produksi.
- Buat runbook deploy, migration, backup, restore, secret rotation, incident, dan rollback.
- Dokumentasikan data retention dan permintaan penghapusan/koreksi.
- Buat restore drill dari backup terenkripsi.
- Buat release checklist dan ownership matrix.

**Acceptance criteria:**

- Operator lain dapat melakukan setup dari dokumen.
- Backup berhasil direstore ke environment terisolasi.
- RTO/RPO dicatat dan diuji.

**Verifikasi:** tabletop exercise dan restore drill.

## Sprint 22 — Staging, UAT, security regression, dan release candidate

**Tujuan:** Membuktikan release candidate di lingkungan menyerupai produksi.

**Pekerjaan:**

- Deploy staging dengan data sintetis.
- Jalankan full test, role matrix, CSRF, session, import/export, migration, performance, dan accessibility.
- Lakukan UAT siswa, UKS, dan orang tua.
- Freeze release candidate dan buat checksum paket.
- Siapkan release notes dan known limitations.

**Acceptance criteria:**

- Tidak ada critical/high unresolved finding.
- Semua critical user journey lulus.
- Feature klinis tetap OFF jika CP-07 belum GREEN.
- UAT ditandatangani pemilik produk.

**Verifikasi:** automated suite dan UAT evidence.

**Checkpoint CP-10:** Go/No-Go release candidate.

## Sprint 23 — Persiapan produksi `akrab.portodq.com`

**Tujuan:** Menyiapkan infrastruktur produksi secara aman dan reversible.

**Pekerjaan:**

- Verifikasi document root, PHP version/extensions, HTTPS, DNS, timezone `Asia/Jakarta`, cron, disk quota, dan permission.
- Buat backup penuh file dan database sebelum perubahan.
- Buat user database runtime least-privilege untuk database `u602402025_akrab`.
- Masukkan password database yang disediakan melalui secret/environment hosting, bukan source.
- Uji koneksi menggunakan health check aman.
- Dry-run migrasi pada clone database produksi.
- Siapkan paket versi sebelumnya, maintenance page, dan langkah rollback.

**Acceptance criteria:**

- Backup dapat dibaca dan restore dry-run berhasil.
- Secret tidak muncul di file publik, Git, paket, log, atau output test.
- Migrasi clone selesai tanpa kehilangan data.
- SSL dan security headers valid.

**Verifikasi:** infrastructure checklist, secret scan, backup restore test, migration rehearsal.

**Checkpoint CP-11:** persetujuan eksplisit Go/No-Go produksi.

## Sprint 24 — Deployment produksi dan stabilisasi

**Tujuan:** Mendeploy secara bertahap ke `https://akrab.portodq.com/`.

**Pekerjaan sebelum deploy:**

- Pilih jendela maintenance dan tetapkan deploy owner serta rollback owner.
- Rekam baseline error, latency, dan database metrics.
- Aktifkan maintenance mode.
- Ambil backup final database dan file.

**Urutan deployment:**

1. Upload release ke direktori versi baru, bukan overwrite langsung.
2. Inject konfigurasi/secret produksi.
3. Jalankan preflight dan lint.
4. Jalankan migrasi versioned.
5. Alihkan document root/symlink ke release baru.
6. Jalankan health check dan smoke test role utama.
7. Matikan maintenance mode.
8. Pertahankan feature klinis OFF, lalu aktifkan hanya jika CP-07 GREEN.

**Stabilisasi:**

- Pantau intensif 60 menit pertama.
- Pantau harian selama tujuh hari.
- Rotasi password database setelah stabil karena pernah disampaikan melalui percakapan.
- Hapus release lama hanya setelah masa rollback berakhir.

**Acceptance criteria:**

- Domain menghasilkan HTTPS 200 untuk health dan halaman publik.
- Login dan flow siswa/UKS/orang tua berhasil.
- Tidak ada error baru, kebocoran secret, atau anomali data.
- Backup, rollback package, dan audit log tersedia.

**Rollback trigger:**

- Integritas data terganggu.
- Vulnerability aktif ditemukan.
- Error rate lebih dari 2x baseline.
- P95 latency naik lebih dari 50%.
- Critical flow login/kuesioner/dashboard gagal.

**Rollback:**

1. Aktifkan maintenance mode.
2. Arahkan document root ke release sebelumnya.
3. Jalankan migration rollback hanya jika aman; jika destructive, restore backup sesuai runbook.
4. Verifikasi health, login, dan konsistensi data.
5. Catat incident dan hentikan rollout.

**Checkpoint CP-12:** produksi dinyatakan stabil setelah 7 hari tanpa blocker.

## Program Lanjutan — Superadmin Master Dashboard

Specification:
`docs/specs/superadmin-master-dashboard.md`

Status awal: SPEC APPROVED; implementation plan menunggu persetujuan.

### Dependency graph

```text
Role/schema invariant + provisioning
              │
              ├── Authorization policy + feature flag
              │                 │
              │                 └── Superadmin shell/dashboard
              │
              └── Two-identity audit model
                                │
                                └── Login As security kernel
                                      │
                                      ├── Read-only master views
                                      ├── User/link management
                                      ├── Health record management
                                      └── Content/operational management
                                                   │
                                                   └── Reports, UAT, release
```

Implementasi mengikuti dependency graph. Feature flag superadmin tetap OFF
sampai security kernel, route coverage, dan browser UAT lulus.

### Architecture decisions

- `superadmin` menjadi role aplikasi eksplisit, tetapi database menjamin hanya
  satu row superadmin.
- Provisioning dan recovery superadmin hanya melalui CLI.
- Dashboard memakai repository/service/route tipis; tidak ada raw SQL editor.
- Login As menyimpan authenticated actor dan effective actor secara terpisah.
- Audit dua identitas menjadi fondasi sebelum mutasi master dibuka.
- Login As memakai step-up password, reason wajib, expiry 15 menit, session ID
  regeneration, dan central deny policy untuk aksi kritis.
- Tidak ada hard delete. Arsip/koreksi ditambahkan per domain melalui migration
  additive.
- Rollback utama sebelum full release adalah feature flag OFF; migration tidak
  di-rollback secara destructive.
- Seluruh query daftar memakai pagination dan seluruh mutasi memakai CSRF,
  prepared statement, transaction, PRG, confirmation, dan audit.

## Sprint 25 — Superadmin identity and authorization foundation

**Tujuan:** Menambahkan invariant satu-superadmin tanpa membuka dashboard.

**Pekerjaan:**

- Migration role `superadmin`, account status, dan unique singleton constraint.
- Permission matrix serta dashboard mapping superadmin yang fail-closed.
- Feature flag `AKRAB_SUPERADMIN_ENABLED` default OFF.
- CLI provisioning/recovery yang idempotent dan tidak mencetak secret.
- Test fresh/existing/idempotent migration dan penolakan superadmin kedua.

**Acceptance criteria:**

- Tepat satu akun superadmin dapat diprovision melalui CLI.
- Registrasi publik tidak dapat membuat atau memilih role superadmin.
- Role lama tidak memperoleh permission baru.
- Saat flag OFF, route/login superadmin tetap tidak dapat digunakan.

**Verifikasi:** focused unit/integration tests, migration rehearsal, lint,
secret scan, dan review schema.

**Checkpoint CP-13:** identity foundation GREEN sebelum route dashboard dibuat.

## Sprint 26 — Two-identity audit and Login As security kernel

**Tujuan:** Membangun lifecycle Login As yang aman sebelum modul master
memiliki mutasi.

**Pekerjaan:**

- Migration `impersonation_sessions` dan perluasan audit dua identitas.
- Service start/end/expire dengan step-up authentication dan reason wajib.
- Session state authenticated/effective actor yang tidak dapat dipalsukan.
- Central deny policy untuk credential, role/status, archive/delete, export
  massal, konfigurasi, clinical gate, dan nested impersonation.
- Audit otomatis untuk setiap request mutasi selama Login As.

**Acceptance criteria:**

- Login As hanya dapat dimulai oleh session superadmin asli.
- Target hanya akun aktif dengan role siswa, UKS, atau orang tua.
- Expiry maksimal 15 menit diperiksa pada setiap request.
- Mutasi operasional menyimpan dua identitas; aksi kritis selalu 403.
- Start/end/expiry meregenerasi session ID dan tercatat di audit.

**Verifikasi:** session fixation, CSRF, nested impersonation, expiry bypass,
forged actor/target, audit integrity, dan transaction tests.

**Checkpoint CP-14:** security kernel GREEN sebelum Login As ditampilkan.

## Sprint 27 — Superadmin shell and read-only master dashboard

**Tujuan:** Memberikan visibilitas terpusat tanpa mutasi bisnis.

**Pekerjaan:**

- Layout accessible/responsive khusus superadmin.
- Overview repository untuk metrik akun, relasi, konsultasi, artikel, TTD,
  health, migration, dan clinical flag read-only.
- Daftar/detail pengguna dengan search, filter, pagination, dan status.
- Audit viewer dengan filter actor/effective actor/action/outcome/request ID.
- Feature flag dan route guard di seluruh halaman superadmin.

**Acceptance criteria:**

- Hanya superadmin asli yang dapat membuka dashboard.
- Semua list terpaginate dan tidak memuat password hash/secret.
- Metrik tidak menghitung row yang kelak diarsip.
- Clinical flag terlihat tetapi tidak dapat diubah.
- Tidak ada query N+1 pada critical dashboard paths.

**Verifikasi:** authorization tests, repository integration tests, EXPLAIN,
accessibility/keyboard check, responsive browser smoke, dan console/network
check.

**Checkpoint CP-15:** read-only dashboard GREEN sebelum mutasi master dibuka.

## Sprint 28 — User and parent-link management

**Tujuan:** Mengelola lifecycle akun dan relasi tanpa hard delete.

**Pekerjaan:**

- Create siswa/UKS/orang tua dengan validation dan password hash.
- Koreksi field allowlisted; password tidak ditampilkan/dihasilkan dashboard.
- Aktif/nonaktif/arsip akun dengan confirmation dan reason.
- Approve/reject/koreksi/arsip relasi orang tua–siswa.
- Proteksi akun superadmin dan akun yang memiliki dependensi data.

**Acceptance criteria:**

- Tidak ada route yang dapat menghapus row pengguna secara permanen.
- Role conversion akun berdata ditolak.
- Superadmin tidak dapat mengubah status/role dirinya.
- Seluruh perubahan user/link transactional dan tercatat di audit.
- Login As tidak dapat menjalankan aksi lifecycle akun.

**Verifikasi:** validation, CSRF, IDOR, privilege escalation, FK/data-integrity,
audit, dan browser UAT user/link.

**Checkpoint CP-16:** account lifecycle GREEN.

## Sprint 29 — Student health master data

**Tujuan:** Meninjau, mengoreksi, dan mengarsip data kesehatan secara aman.

**Pekerjaan:**

- Migration metadata correction/archive untuk kuesioner, hasil existing,
  kadar Hb, konsumsi TTD, dan riwayat menstruasi.
- Read-only detail history dan pagination/filter.
- Koreksi field allowlisted dengan before/after audit tanpa menyimpan PII
  mentah di metadata.
- Archive/restore sesuai policy, tanpa hard delete.
- Rekonsiliasi agregat agar row archived tidak dihitung.

**Acceptance criteria:**

- Foreign key dan histori existing tetap utuh.
- Koreksi memakai validasi domain existing dan transaction.
- Audit mencatat field name/outcome, bukan nilai kesehatan sensitif mentah.
- Login As boleh melakukan mutasi operasional target tetapi tidak
  correction/archive master.
- Clinical model/flag tetap tidak berubah.

**Verifikasi:** migration rehearsal, validation boundary, ownership/IDOR,
aggregate consistency, audit redaction, dan browser UAT.

**Checkpoint CP-17:** health master data GREEN dengan clinical feature OFF.

## Sprint 30 — Consultation, education, and notification management

**Tujuan:** Mengelola modul operasional non-akun dari dashboard master.

**Pekerjaan:**

- Tinjau/koreksi status/arsip konsultasi dan balasan.
- Kelola artikel edukasi dan saran edukasi lintas UKS dengan audit ownership.
- Tinjau jadwal/log notifikasi dan koreksi status yang diizinkan.
- Tambahkan metadata archive/correction additive per tabel terkait.
- Pastikan seluruh daftar search/filter/pagination.

**Acceptance criteria:**

- Tidak ada hard delete atau silent ownership bypass.
- Perubahan konsultasi menjaga state consistency.
- Konten tetap di-output dengan encoding aman.
- Login As hanya mendapat aksi operasional role target.
- Setiap master correction/archive memiliki reason dan audit.

**Verifikasi:** state transition tests, XSS/output encoding, IDOR, CSRF,
pagination, transaction rollback, audit, dan browser UAT.

**Checkpoint CP-18:** operational master modules GREEN.

## Sprint 31 — Login As user experience and route coverage

**Tujuan:** Membuka Login As setelah security kernel dan seluruh route siap.

**Pekerjaan:**

- Target picker terpaginate dari dashboard superadmin.
- Re-authentication form dan reason validation.
- Banner permanen/countdown/tombol kembali pada seluruh halaman tiga role.
- Route coverage matrix untuk semua GET/POST/export/profile endpoints.
- Browser journey siswa, UKS, dan orang tua termasuk mutasi allowed/blocked.

**Acceptance criteria:**

- Tidak ada protected page yang kehilangan banner saat impersonating.
- Back/refresh/direct URL tidak melewati expiry atau blocked-action policy.
- End Login As selalu kembali ke dashboard superadmin asli.
- Console/network bersih dan focus/keyboard order benar.
- Audit dapat menelusuri setiap journey dari request ID.

**Verifikasi:** route coverage unit test, Selenium/Chrome isolated-profile UAT,
accessibility tree, network/console inspection, dan session regression.

**Checkpoint CP-19:** Login As GREEN.

## Sprint 32 — Reports, hardening, UAT, and controlled release

**Tujuan:** Menutup seluruh gate sebelum feature flag dinyalakan.

**Pekerjaan:**

- Laporan agregat dan export superadmin dengan confirmation/audit.
- Security review lima sumbu, performance baseline, dan dependency audit.
- Full unit/integration/security/browser regression.
- Fresh/existing migration rehearsal, backup, rollback drill, dan release
  artifact checksum.
- Human UAT superadmin plus Login As tiga role.
- Deploy terkontrol hanya ke `akrab.portodq.com` dengan flag OFF, smoke test,
  lalu enable setelah approval.

**Acceptance criteria:**

- Tidak ada critical/high unresolved finding.
- Tidak ada secret/PII mentah pada source, log, audit, atau release package.
- P95 critical dashboard queries memenuhi budget yang disepakati dari baseline.
- Rollback flag OFF dan package sebelumnya siap.
- Existing siswa/UKS/orang tua journeys tidak regresi.
- Product/security owner memberi GO tertulis.

**Verifikasi:** composer quality pada PHP lengkap, Python model tests, migration
rehearsal, security suite, browser UAT, HTTP/headers/health smoke, log/error
review, dan rollback drill.

**Checkpoint CP-20:** superadmin release GREEN.

### Program risks and mitigations

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Login As menyamarkan pelaku asli | Kritis | Dua identitas immutable dan audit request-level |
| Superadmin kedua tercipta | Kritis | Unique DB invariant plus idempotent CLI |
| Aksi kritis lolos saat impersonation | Kritis | Central deny policy plus route coverage test |
| Data kesehatan bocor di audit/export | Kritis | Field allowlist, redaction, export gate |
| Hard delete merusak integritas | Kritis | Archive-only UI dan tidak ada delete endpoint |
| Migration enum/status gagal pada MariaDB | Tinggi | Clone rehearsal dan idempotent migration |
| Dashboard membuat query berat | Tinggi | Pagination, aggregate query, EXPLAIN, P95 budget |
| Role lama memperoleh akses | Tinggi | Fail-closed policy dan vertical escalation suite |
| Clinical feature aktif tanpa owner | Kritis | Read-only status dan gate tetap di config |
| Deploy menyentuh domain lain | Kritis | Exact-domain/root guard dan artifact allowlist |

### Release order

1. CP-13 sampai CP-19 harus GREEN secara berurutan.
2. Build release dengan feature flag superadmin OFF.
3. Backup dan migration rehearsal pada clone.
4. Deploy hanya ke AKRAB, jalankan migration dan smoke.
5. Jalankan human UAT dan audit verification.
6. Enable flag setelah CP-20 GO.
7. Pantau error, latency, session, dan audit selama stabilization window.

## Strategi pengujian minimum

| Lapisan | Cakupan |
|---|---|
| Unit | validator, kategori, CSRF, scoring, mapping, redaction |
| Integration | PDO repository, migration, transaksi, idempotensi |
| Security | session fixation, CSRF, privilege escalation, enumeration, formula injection |
| Golden | parity model Python-PHP |
| E2E | daftar/login, kuesioner, TTD, konsultasi, approval orang tua, export |
| Performance | dashboard UKS, daftar siswa, laporan, import CSV |
| Browser | PWA, cache update, responsive, keyboard, accessibility |
| Deployment | preflight, health, smoke, migration, rollback drill |

## Risiko dan mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Model memberi hasil klinis salah | Kritis | Flag OFF, clinical owner, golden tests |
| Migrasi merusak database lama | Kritis | Clone rehearsal, backup, reversible migration |
| Secret bocor dari ZIP/source | Kritis | Environment secret, scan, rotation |
| Parent mengakses siswa yang salah | Tinggi | Verified relation dan approval |
| Statistik TTD ganda | Tinggi | Unique constraint dan idempotensi |
| Shared hosting membatasi fitur | Sedang | Preflight Sprint 23 dan fallback runbook |
| Refactor mengubah perilaku | Tinggi | Characterization/regression tests |
| Deployment big-bang | Tinggi | Versioned release, maintenance, rollback |

## Estimasi keseluruhan

- 24 sprint × 3-5 hari kerja: sekitar 72-120 hari kerja.
- Dengan 2 jalur kerja yang terkoordinasi, beberapa sprint UI, dokumentasi, dan test dapat berjalan paralel setelah kontrak/schema stabil.
- Sprint 13-15 mengikuti jadwal clinical owner dan tidak boleh dipercepat hanya demi tanggal deploy.

## Persetujuan yang diperlukan

- Product owner: scope dan UAT.
- Clinical owner: spesifikasi, threshold, model card, dan konten kesehatan.
- Data owner/sekolah: kebijakan akses orang tua, retensi, audit.
- Technical reviewer: schema, security, migration, rollback.
- Deployment owner: akses hosting, secret, backup, dan Go/No-Go.

## Addendum 2026-08-09 — Hasil Kuesioner Ringkas/Lengkap

Spesifikasi: `docs/specs/questionnaire-results.md`

Keputusan data: `docs/decisions/0002-questionnaire-answer-snapshots.md`

### Dependency graph

```text
Eligibility 17 Agustus
  -> penyimpanan snapshot jawaban
      -> presentasi hasil bersama
          -> siswa
          -> parent / UKS / superadmin
              -> full quality + migration rehearsal + browser UAT
```

### Tahap implementasi

1. Ekstrak aturan eligibility agar tanggal 17 Agustus dan cooldown enam bulan
   diuji dengan clock deterministik.
2. Tambahkan migrasi additive dan snapshot jawaban versioned dalam transaksi
   pengiriman kuesioner.
3. Tambahkan service presentasi dan renderer bersama untuk Hasil Ringkas serta
   Hasil Lengkap, termasuk fallback data lama.
4. Integrasikan ke siswa, parent, detail UKS, dan detail superadmin tanpa
   memperluas hak akses.
5. Jalankan lint/test, rehearsal migrasi fresh-existing-idempotent, security
   review, serta browser UAT. Deployment produksi tetap menunggu GO eksplisit.

### Risiko dan mitigasi

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Snapshot memuat data kesehatan sensitif | Tinggi | Boundary role existing, output encoding, tanpa logging |
| Data historis tidak punya jawaban | Sedang | Fallback eksplisit; tidak merekonstruksi jawaban |
| Siswa mengisi berulang pada 17 Agustus | Tinggi | Pengisian pada/setelah cutoff memulai cooldown baru |
| Perubahan memengaruhi model klinis | Kritis | Tidak mengubah input, formula, threshold, atau flag model |
| Migrasi production gagal | Tinggi | Additive migration, clone rehearsal, backup, explicit GO |
