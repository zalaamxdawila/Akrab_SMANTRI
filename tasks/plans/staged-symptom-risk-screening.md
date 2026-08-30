# Implementation Plan: Skrining Bertahap Gejala dan Faktor Risiko

Spec: `docs/specs/staged-symptom-risk-screening.md`

## Dependency Graph

```text
Persetujuan formula faktor risiko/claim/rujukan
  -> kontrak scoring versioned
      -> migrasi additive + profil biodata
          -> slice tahap gejala + hasil awal
              -> gate server faktor risiko
                  -> slice faktor risiko + rekomendasi
                      -> laporan lintas role + compatibility data lama
                          -> clinical review + UAT + release gate
```

## Phase 0 — Clinical Contract (fail fast)

### Task SR0: Bekukan tabel keputusan ahli

**Description:** Ubah rekomendasi ahli menjadi tabel contoh input-output yang
melengkapi keputusan produk yang sudah final (`> 4,6` dan `< 75%`) dengan bobot,
denominator, pembulatan, kategori, red flag, dan jalur rujukan.

**Acceptance criteria:**
- Pertanyaan gejala berasal dari Bagian III `Kuesioner.pdf` dan urutannya tetap.
- Faktor internal/eksternal memakai bagian PDF yang disetujui ahli.
- Ahli medis sekolah terkait menyetujui dokumen versi dan contoh boundary.
- Istilah keluaran adalah skrining risiko, bukan diagnosis.
- Golden cases menetapkan: `4,6` terkunci, nilai di atasnya lanjut; `74,9%`
  terindikasi risiko dan `75,0%` tidak.

**Verification:** Review dan tanda persetujuan ahli medis sekolah terkait.

**Dependencies:** None.

**Files likely touched:** spec klinis/model card dan fixture golden cases.

**Estimated scope:** Small.

## Phase 1 — Foundation

### Task SR1: Tambahkan kontrak biodata onboarding siswa

**Description:** Tambahkan field profil yang disetujui, validasi server, route
pengisian biodata, dan prioritas onboarding sebelum skrining.

**Acceptance criteria:**
- Kelas, tanggal lahir/usia, dan gender tervalidasi dan tersimpan pada profil.
- Siswa dengan biodata belum lengkap diarahkan ke form biodata setelah login.
- Siswa lengkap dapat menuju tahap gejala tanpa meminta data yang sama lagi.

**Verification:** Migration fresh/existing/idempotent, unit onboarding, integration
authorization/CSRF, dan browser login flow.

**Dependencies:** SR0.

**Files likely touched:** migration baru, `database/schema.sql`, `helpers.php`,
route biodata siswa, test onboarding.

**Estimated scope:** Medium; pecah migrasi dan UI menjadi commit terpisah.

### Task SR2: Buat domain model skrining bertahap yang versioned

**Description:** Tambahkan status tahap, skor gejala, skor faktor risiko, versi
instrumen/aturan, timestamps, dan snapshot additive tanpa merusak data lama.

**Acceptance criteria:**
- State hanya bergerak melalui transisi yang diizinkan.
- Hasil lama tetap dapat dibaca tanpa backfill klinis palsu.
- Submit ulang/idempotensi tidak menghasilkan state parsial atau duplikat.

**Verification:** Migration rehearsal dan integration test transaksi/state.

**Dependencies:** SR0.

**Files likely touched:** migration baru, `database/schema.sql`, service/repository
skrining, integration tests.

**Estimated scope:** Medium.

### Checkpoint A — Contract dan Foundation

- [ ] Clinical contract disetujui.
- [ ] Migrasi fresh/existing/idempotent lulus.
- [ ] Data historis dan flow login lama tidak regresi.

## Phase 2 — Vertical Slice Gejala

### Task SR3: Implementasikan scoring gejala murni dan golden tests

**Description:** Buat calculator deterministik berbasis kontrak SR0, tanpa Hb
dan tanpa membaca nilai skor dari client. Definition instrumen memuat sepuluh
pertanyaan Bagian III `Kuesioner.pdf` beserta urutan dan versi sumber.

**Acceptance criteria:**
- Semua jawaban divalidasi dan skor dihitung server-side.
- Skor adalah jumlah sepuluh jawaban dibagi 10; boundary `4,5`, `4,6`, dan
  `4,7` cocok dengan tabel keputusan.
- Versi aturan dan checksum tersimpan bersama hasil.

**Verification:** Unit tests untuk valid, invalid, tampering, rounding, missing,
dan red flags.

**Dependencies:** SR0, SR2.

**Files likely touched:** service scoring, config/definition instrumen, unit tests.

**Estimated scope:** Small.

### Task SR4: Bangun tahap UI dan submit gejala

**Description:** Ganti kuesioner satu halaman dengan tahap gejala saja dan hasil
awal yang tersimpan atomik.

**Acceptance criteria:**
- Hanya sepuluh pertanyaan Bagian III `Kuesioner.pdf` yang terlihat/terkirim
  pada tahap pertama; Hb, sikap, pengetahuan, menstruasi, dan pola makan tidak
  ikut dalam payload tahap ini.
- Gagal validasi tidak menyimpan hasil parsial.
- Siswa hanya dapat membaca/mengubah screening miliknya sendiri.

**Verification:** Integration submit, CSRF/IDOR/tampering tests, accessibility,
dan browser flow mobile/desktop.

**Dependencies:** SR1, SR2, SR3.

**Files likely touched:** `siswa/kuesioner.php`, service/snapshot, result route,
integration/browser tests.

**Estimated scope:** Medium.

### Task SR5: Tampilkan hasil gejala dan gate faktor risiko

**Description:** Tampilkan skor, penjelasan, disclaimer, dan keputusan lanjut;
enforce akses faktor risiko di server.

**Acceptance criteria:**
- Tidak lolos ambang: faktor risiko terkunci dan hasil gejala tetap tersedia.
- Lolos ambang: CTA faktor risiko muncul.
- Direct URL/forged POST faktor risiko ditolak bila gate tidak terpenuhi.

**Verification:** Unit presenter, integration authorization/state, browser
boundary flows, dan output encoding.

**Dependencies:** SR4.

**Files likely touched:** gate/presenter service, shared result view, route faktor
risiko, tests.

**Estimated scope:** Medium.

### Checkpoint B — Gejala End-to-End

- [ ] Dua cabang threshold lulus end-to-end.
- [ ] Bypass client gagal.
- [ ] Disclaimer dan red-flag guidance disetujui ahli.

## Phase 3 — Vertical Slice Faktor Risiko

### Task SR6: Implementasikan instrumen dan scoring faktor risiko

**Description:** Validasi jawaban faktor risiko dan hitung hasil sesuai kontrak
SR0 tanpa Hb. Kandidat awal adalah lima pertanyaan menstruasi Bagian VI sebagai
faktor internal serta tabel/kebiasaan pola makan Bagian VII sebagai faktor
eksternal.

**Acceptance criteria:**
- Teks, urutan, dan pilihan jawaban sesuai bagian VI/VII `Kuesioner.pdf` yang
  disetujui pada SR0.
- Bobot, denominator, dan rounding disetujui; `74,9%` dan `75,0%` cocok dengan
  tabel keputusan produk.
- Calculator menolak field asing/manipulasi skor.
- Hasil menyimpan versi aturan dan penjelasan faktor dominan yang diizinkan.

**Verification:** Unit golden/boundary/property-style tests.

**Dependencies:** SR0, SR2, SR5.

**Files likely touched:** risk-factor calculator/definition dan unit tests.

**Estimated scope:** Small.

### Task SR7: Bangun tahap faktor risiko dan hasil akhir

**Description:** Tambahkan form hanya untuk siswa eligible, submit atomik, hasil
akhir, dan rekomendasi berjenjang.

**Acceptance criteria:**
- Form hanya tersedia setelah gejala eligible.
- Saran UKS/Puskesmas/dokter mengikuti decision table dan red flags.
- UI memakai “terindikasi/berisiko anemia”, tidak mengklaim diagnosis, dan tidak
  mewajibkan lab.

**Verification:** Integration state/authorization/CSRF, unit presenter, clinical
content snapshot, accessibility, dan browser tests.

**Dependencies:** SR6.

**Files likely touched:** route/view faktor risiko, service persistence/presenter,
tests.

**Estimated scope:** Medium.

### Checkpoint C — Faktor Risiko End-to-End

- [ ] Seluruh golden cases dan jalur rujukan lulus.
- [ ] Review klinis untuk setiap teks hasil lulus.
- [ ] Tidak ada ketergantungan Hb pada flow baru.

## Phase 4 — Consumers, Compatibility, and Release

### Task SR8: Adaptasikan laporan UKS, superadmin, siswa, dan parent

**Description:** Tampilkan status tahap dan hasil versioned tanpa mencampur makna
hasil baru dengan model lab lama.

**Acceptance criteria:**
- Setiap audience tunduk pada boundary akses existing.
- Data lama memiliki label legacy yang jelas dan tetap terbaca.
- Export/aggregate membedakan versi instrumen dan status completion.

**Verification:** Repository integration, role-boundary, aggregate/export, dan
browser tests.

**Dependencies:** SR7.

**Files likely touched:** analytics repository/presenters/views dan tests; pecah
per consumer agar setiap task maksimal sekitar lima file.

**Estimated scope:** Large, wajib dipecah menjadi subtask per consumer.

### Task SR9: Quality, UAT, migration rehearsal, dan rollout

**Description:** Jalankan quality gate, clinical approval, UAT siswa/UKS, backup,
rollback rehearsal, lalu staged deployment hanya ke `akrab.portodq.com`.

**Acceptance criteria:**
- Lint/test/security/browser suite hijau; tidak ada critical/high finding.
- Clinical flag tetap OFF sampai approval dan UAT ditandatangani.
- Backup dan rollback release lama terverifikasi.
- Deploy hanya ke document root `/home/u602402025/domains/portodq.com/public_html/akrab`.

**Verification:** `composer quality`, migration clone rehearsal, secret scan,
release preflight, authenticated UAT, health/error monitoring pascarilis.

**Dependencies:** SR8 dan explicit production GO.

**Files likely touched:** checkpoint, runbook/release notes, config produksi di
luar repository.

**Estimated scope:** Medium.

## Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Arah threshold salah | Kritis | Golden boundary table dan persetujuan ahli medis sekolah sebelum coding |
| Salah klasifikasi bagian PDF sebagai faktor risiko | Kritis | Pemetaan VI/VII harus disetujui ahli pada SR0 |
| Perbedaan skala gejala 0–10 vs 1–10 | Tinggi | Putuskan dari instrumen asli dan uji nilai minimum/maksimum |
| Klaim “mendeteksi anemia” dianggap diagnosis | Kritis | Gunakan skrining risiko, disclaimer, dan rujukan |
| Gate hanya di frontend | Tinggi | State machine dan authorization server-side |
| Hasil lama berubah makna | Tinggi | Versioning; no reinterpretation/backfill |
| PII/health data bocor | Tinggi | Existing role boundary, audit redaction, no URL/log data |
| Saran terlambat pada red flag | Tinggi | Red-flag override sebelum aggregate score |
| Worktree saat ini sudah kotor | Sedang | Perubahan kecil, hindari file user yang tidak terkait, diff per task |

## Planning Exit Gate

Implementasi tidak dimulai sampai SR0 disetujui dan open questions pada spec
ditutup. Setelah itu implementasi dilakukan vertikal dengan test-first dan satu
checkpoint setiap 2–3 task.

Ambang dan arah perbandingan bukan lagi open question: gejala memakai rata-rata
10 jawaban dengan gate `> 4,6`; faktor risiko `< 75%` menghasilkan status
terindikasi risiko. SR0 tetap diperlukan untuk formula/bobot faktor risiko dan
decision table rujukan.

Dalam rencana ini, istilah `clinical owner` pada roadmap lama harus dibaca sebagai
ahli medis sekolah terkait. Tidak ada kerja sama atau afiliasi dengan WHO,
Kementerian Kesehatan, maupun organisasi eksternal lain.
