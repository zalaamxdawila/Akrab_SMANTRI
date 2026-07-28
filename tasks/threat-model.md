# Threat Model Awal AKRAB

## Aset

- Identitas dan data kesehatan siswa.
- Relasi orang tua-anak.
- Kredensial pengguna dan sesi.
- Kredensial database dan konfigurasi produksi.
- Hasil skrining, konsultasi, laporan, dan audit trail.

## Trust boundary

1. Browser publik ke endpoint PHP.
2. Sesi pengguna ke otorisasi role dan ownership.
3. PHP runtime ke database MySQL.
4. Upload/import CSV ke parser dan database.
5. Export CSV/PDF/kalender ke perangkat pengguna.
6. Cron ke tabel notifikasi.
7. CDN dan service worker ke browser.

## STRIDE dan abuse case prioritas

| Ancaman | Abuse case | Kontrol yang direncanakan |
|---|---|---|
| Spoofing | Session fixation atau brute-force login | Secure session, regeneration, rate limit |
| Tampering | CSRF mencatat TTD atau menghapus artikel | POST-only, CSRF token, idempotensi |
| Repudiation | Petugas menyangkal akses/export data | Audit log dengan actor dan outcome |
| Information disclosure | Orang tua menautkan NISN tanpa verifikasi | Approval UKS dan relasi terverifikasi |
| Denial of service | CSV besar atau request login berulang | Size/row limit, timeout, rate limit |
| Elevation of privilege | Registrasi UKS memakai kode statis | Tutup self-registration UKS |
| Clinical harm | Model mock memberi hasil aman yang salah | Feature flag OFF dan validasi klinis |

## Keputusan Sprint 0

- Output klinis fail-closed.
- Tidak ada database production write pada Sprint 0.
- Secret tidak ditambahkan ke source.
- Utility debug tetap dicatat sebagai blocker Sprint 2.

