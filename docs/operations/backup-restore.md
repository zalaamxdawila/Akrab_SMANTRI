# Backup and Restore Runbook

## Target

- RPO: maksimum 24 jam sebelum ada dukungan backup lebih sering dari hosting.
- RTO: 4 jam untuk pemulihan terverifikasi.
- Retensi: harian 14 hari, mingguan 8 minggu, bulanan 12 bulan; sesuaikan dengan kebijakan institusi.

## Backup

1. Aktifkan maintenance/read-only window untuk backup konsisten bila snapshot database tidak tersedia.
2. Export database menggunakan fasilitas hosting atau `mysqldump` dengan transaksi tunggal, routine/trigger sesuai kebutuhan, tanpa password pada command line.
3. Arsipkan release aktif, file konfigurasi non-secret, versi PHP/MySQL, dan daftar cron.
4. Enkripsi backup sebelum meninggalkan hosting.
5. Catat checksum, ukuran, waktu, database source, release commit, dan operator pada register backup terpisah.
6. Simpan minimal satu salinan off-host dengan akses terbatas.

## Restore drill

1. Buat database dan direktori restore yang terisolasi; jangan gunakan produksi.
2. Verifikasi checksum dan decrypt menggunakan secret store operator.
3. Import dump ke database kosong.
4. Pasang release yang sesuai dengan metadata backup.
5. Jalankan status migrasi, health, login sintetis, dan pemeriksaan jumlah tabel/record tanpa membuka PII.
6. Catat durasi, RPO aktual, RTO aktual, error, dan keputusan PASS/FAIL.
7. Hapus clone drill sesuai prosedur data sensitif setelah bukti disimpan.

## Production restore

Restore produksi memerlukan persetujuan data owner dan incident commander. Backup produksi aktif tidak boleh ditimpa; simpan snapshot keadaan rusak untuk forensik. Setelah restore, rotasi secret yang mungkin terpapar dan lakukan rekonsiliasi transaksi sejak titik RPO.
