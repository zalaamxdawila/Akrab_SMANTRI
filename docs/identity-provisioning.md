# Provisioning Identitas AKRAB

## Akun UKS

Akun UKS tidak dapat dibuat melalui halaman registrasi publik. Operator deployment menjalankan:

```text
php tools/provision_uks.php --username=<identifier> --name="<nama petugas>"
```

Sebelum perintah dijalankan:

1. Jalankan migration terbaru.
2. Isi `AKRAB_PROVISION_UKS_PASSWORD` dengan password sementara acak minimal 12 karakter pada environment CLI.
3. Pastikan akun migration tersedia melalui `AKRAB_MIGRATION_DB_USER` dan `AKRAB_MIGRATION_DB_PASS`.
4. Jalankan perintah satu kali, lalu hapus `AKRAB_PROVISION_UKS_PASSWORD` dari environment.
5. Serahkan password melalui kanal terpisah dan minta petugas menggantinya setelah login pertama.

Tool tidak menerima password sebagai argumen command line sehingga password tidak muncul pada process list atau shell history. Provisioning dicatat sebagai `uks.provisioned` pada `audit_log`.

## Tautan orang tua–siswa

1. Orang tua mendaftar dengan NISN yang diklaim.
2. Sistem menyimpan permintaan `pending` tanpa memberitahu apakah NISN tersebut ada.
3. Dashboard orang tua tidak menampilkan data kesehatan selama permintaan belum disetujui.
4. UKS memeriksa bukti hubungan pada halaman `uks/kelola_tautan.php`.
5. Saat disetujui, sistem mencari user siswa yang cocok dan menyimpan reviewer serta waktu keputusan.
6. Request dan keputusan approval/rejection tercatat pada `audit_log`.

UKS harus memverifikasi identitas di luar aplikasi sesuai prosedur sekolah sebelum menekan tombol setujui.
