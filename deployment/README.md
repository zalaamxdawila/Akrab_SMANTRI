# Deployment Package Policy

Build paket produksi dari allowlist `include.txt`, lalu terapkan `exclude.txt`.
Jangan menyalin seluruh workspace secara langsung ke web root.

Sebelum paket dipromosikan:

1. Pastikan `config.php` dan `helpers.php` dilindungi `.htaccess`.
2. Pastikan konfigurasi environment tersedia pada hosting.
3. Jalankan lint dan test.
4. Jalankan secret scan terhadap isi paket.
5. Verifikasi utility debug, test, dokumentasi internal, source model, backup, dan arsip tidak terdapat pada paket.
