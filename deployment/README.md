# Deployment Package Policy

Build paket produksi dari allowlist `include.txt`, lalu terapkan `exclude.txt`.
Jangan menyalin seluruh workspace secara langsung ke web root.

Sebelum paket dipromosikan:

1. Pastikan `config.php` dan `helpers.php` dilindungi `.htaccess`.
2. Pastikan konfigurasi environment tersedia pada hosting.
3. Jalankan lint; test runtime dilakukan pada deployment sesuai keputusan checkpoint aktif.
4. Jalankan secret scan terhadap isi paket.
5. Verifikasi utility debug, test, dokumentasi internal, source model, backup, dan arsip tidak terdapat pada paket.
6. Pastikan `app/`, `views/`, `bootstrap.php`, `health.php`, dan `offline.html` masuk paket.
7. Tempatkan cron di lokasi terlindungi; `.htaccess` harus menolak akses HTTP ke `/cron`.
