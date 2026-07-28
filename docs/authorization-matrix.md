# Matriks Authorization AKRAB

Semua aksi yang tidak tercantum ditolak secara default. Role yang sah hanya `siswa`, `orangtua`, dan `uks`.

| Resource / aksi | Siswa | Orang tua | UKS | Batas objek |
|---|---:|---:|---:|---|
| Data kesehatan sendiri | Kelola | Tidak | Baca | Siswa memakai `user_id` dari session |
| Data anak tertaut | Tidak | Baca | Baca | Orang tua hanya melalui relasi database berstatus `approved` |
| Konsultasi | Buat dan baca sendiri | Tidak | Baca dan jawab | Balasan hanya untuk konsultasi siswa berstatus menunggu |
| Artikel edukasi | Baca | Tidak | Kelola | UKS hanya mengubah/menghapus artikel dengan `uks_id` sendiri |
| Surat rujukan | Tidak | Tidak | Cetak | Target wajib user dengan role siswa |
| Import/export siswa | Tidak | Tidak | Kelola | Dijaga role UKS |
| QR siswa | Milik sendiri | Tidak | Pindai | Payload dianggap NISN, tidak pernah URL redirect |

## Aturan implementasi

- Identitas aktor selalu diambil dari session, bukan request.
- ID dari request dikonversi ke integer sebelum query.
- Query objek privat wajib menyertakan owner atau role target.
- Role tidak dikenal dan role yang tidak cocok menghasilkan HTTP 403.
- Login hanya menerima role yang terdaftar dan tidak memiliki fallback privilege.
- Operasi yang bersaing memakai transaction dan row lock bila diperlukan.

## Privilege database

Gunakan dua akun database yang berbeda:

- Akun runtime (`AKRAB_DB_USER`) hanya membutuhkan `SELECT`, `INSERT`, `UPDATE`, dan `DELETE` pada schema aplikasi.
- Akun migration (`AKRAB_MIGRATION_DB_USER`) dipakai hanya oleh CLI dan memiliki izin perubahan schema yang diperlukan migration.

Akun runtime tidak boleh memiliki `CREATE`, `ALTER`, `DROP`, `INDEX`, `GRANT`, atau privilege administratif. Nilai secret keduanya hanya disimpan pada environment hosting.
