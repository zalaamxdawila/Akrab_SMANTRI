# Baseline Teknis AKRAB — Sprint 0

Tanggal: 2026-07-29

## Runtime dan struktur

- PHP native + PDO MySQL.
- PHP CLI verifikasi: 8.5.8.
- Role aplikasi: `siswa`, `uks`, dan `orangtua`.
- Area utama: root publik, `siswa/`, `uks/`, `orangtua/`, `cron/`, `database/`, dan `assets/`.
- Database produksi yang direncanakan: `u602402025_akrab`.
- Domain produksi yang direncanakan: `https://akrab.portodq.com/`.

## Alur kritis

1. Registrasi dan login.
2. Pengisian kuesioner dan perhitungan risiko.
3. Pencatatan konsumsi TTD dan menstruasi.
4. Konsultasi siswa dengan UKS.
5. Akses orang tua ke data anak.
6. Import/export data siswa.
7. Dashboard, laporan, dan rujukan UKS.

## Baseline verifikasi

- Seluruh 37 file PHP lolos `php -l` sebelum perubahan Sprint 0.
- Tidak ditemukan test framework atau dependency manifest pada baseline.
- Model risiko memakai koefisien mock dan menghasilkan kategori rendah untuk sampel Hb 6, 8, 10, 12, dan 14.
- Fitur klinis harus default OFF sampai CP-07 berstatus GREEN.

## Artefak yang tidak boleh masuk release publik

- `Akrab_APP.zip`
- `database/schema.sql`
- `test_export.php`
- `inject.php`
- `fix_paths.php`
- `zip_project.php`
- `uks/debug_export.php`
- File environment, backup, log, test, dan checkpoint internal.

## Baseline rollback

Arsip awal `Akrab_APP.zip` dipertahankan hanya sebagai referensi lokal dan dikecualikan dari Git serta paket deployment.

- Algoritma: SHA-256
- Checksum: `0BB077B56229D236DE9425D499045422DA38C455AE790B14A4FCA5CDC59079C2`
- Lokasi baseline lokal: `C:\Y\Akrab_APP\Akrab_APP.zip`

Sebelum deployment produksi, checksum dan lokasi backup baru harus dicatat pada checkpoint terkait.
