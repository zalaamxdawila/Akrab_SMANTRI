# Spec: Hasil Kuesioner Ringkas dan Lengkap

## Objective

Menyediakan dua tingkat hasil kuesioner yang jelas tanpa mengubah model risiko:

- Hasil Ringkas menampilkan kategori risiko, probabilitas, empat aspek skor,
  faktor yang paling perlu diperhatikan, dan langkah tindak lanjut.
- Hasil Lengkap menampilkan seluruh data pengisian yang tersedia, termasuk
  pertanyaan dan jawaban yang benar-benar diberikan siswa.
- Siswa dan orang tua hanya dapat membaca hasil siswa terkait. UKS dan
  superadmin dapat membaca hasil semua siswa melalui halaman yang sudah
  memiliki pemeriksaan peran.
- Semua siswa dapat mengisi kembali mulai 17 Agustus 2026. Setelah siswa
  mengisi pada atau setelah tanggal itu, jeda enam bulan berlaku kembali.

Keberhasilan berarti hasil mudah dipahami, transparan terhadap sumber skor,
aman untuk data kesehatan, tetap menyatakan bahwa skrining bukan diagnosis,
dan tidak mengubah keluaran model klinis yang sudah ada.

## Tech Stack

- PHP 8.2 native dengan PDO dan session-based authorization.
- MariaDB/MySQL melalui migration runner proyek.
- Bootstrap dan komponen HTML semantik yang sudah tersedia di proyek.
- PHPUnit 12 untuk unit dan integration test.

## Commands

- Lint: `composer lint`
- Unit test: `composer test:unit`
- Integration test: `composer test:integration`
- Full quality gate: `composer quality`
- Python regression: `python -m unittest discover -s tests -p "test_*.py"`
- Migration status: `php tools/migrate.php --status`
- Local migration: `php tools/migrate.php`
- Production migration hanya setelah GO terpisah:
  `php tools/migrate.php --allow-production`

## Project Structure

- `app/Services/` — aturan kelayakan isi ulang, snapshot jawaban, dan presentasi hasil.
- `database/migrations/` — migrasi additive untuk snapshot jawaban.
- `views/questionnaire_analytics.php` — renderer bersama hasil ringkas/lengkap.
- `siswa/`, `orangtua/`, `uks/`, `superadmin/` — permukaan hasil sesuai peran.
- `tests/Unit/` dan `tests/Integration/` — kontrak perilaku, akses, dan persistence.

## Code Style

Gunakan service kecil dengan tipe eksplisit, nilai waktu yang dapat diinjeksi
untuk test, dan output selalu di-escape pada boundary HTML.

```php
$eligibility = new QuestionnaireEligibility();
$status = $eligibility->forLatestSubmission($latestCreatedAt, new DateTimeImmutable('now'));

if (!$status['allowed']) {
    header('Location: dashboard.php?cooldown=1');
    exit;
}
```

## Testing Strategy

- Unit: batas sebelum/pada/setelah 17 Agustus 2026, cooldown setelah pengisian
  baru, pemetaan skor, prioritas faktor, fallback jawaban lama, dan encoding.
- Integration: snapshot jawaban tersimpan atomik bersama kuesioner dan hasil
  risiko; kegagalan tetap rollback seluruh transaksi.
- Authorization: parent memakai relasi approved; siswa memakai session sendiri;
  UKS dan superadmin tetap melalui guard yang ada.
- Browser/manual: keyboard, mobile, dark mode, ringkasan terbaca tanpa membuka
  detail, detail dapat dibuka, dan tidak ada error console/network.
- Migration: fresh, existing, dan rerun idempotent pada clone sebelum produksi.

## Boundaries

- Always: simpan hanya jawaban yang memang ditampilkan dan divalidasi; encode
  output; pertahankan disclaimer; audit akses UKS/superadmin; backup sebelum
  migrasi produksi.
- Ask first: deploy ke produksi, menjalankan migrasi produksi, mengubah tanggal
  buka ulang, atau mengubah formula/threshold/model risiko.
- Never: menampilkan data siswa lain kepada siswa/orang tua; mengarang rincian
  jawaban untuk data lama; memasukkan jawaban kesehatan ke log; mengklaim
  diagnosis medis.

## Success Criteria

1. Halaman hasil siswa memiliki bagian “Hasil Ringkas” dan “Hasil Lengkap”.
2. Parent melihat format yang sama hanya untuk siswa dengan relasi approved.
3. UKS dan superadmin dapat membuka format lengkap untuk setiap siswa.
4. Pengisian baru menyimpan snapshot pertanyaan/jawaban tervalidasi; pengisian
   lama menampilkan pesan bahwa rincian jawaban belum tersedia.
5. Semua siswa dengan pengisian terakhir sebelum 17 Agustus 2026 menjadi
   eligible mulai tanggal tersebut.
6. Pengisian pada/setelah 17 Agustus memulai cooldown enam bulan baru.
7. Model risiko, kategori, dan probabilitas tidak berubah akibat fitur ini.
8. Lint, test, migration rehearsal, security review, dan browser smoke lulus.

## Open Questions

- Tidak ada untuk implementasi lokal.
- Deployment dan migrasi produksi memerlukan GO eksplisit setelah hasil lokal
  serta rehearsal migration dilaporkan.
