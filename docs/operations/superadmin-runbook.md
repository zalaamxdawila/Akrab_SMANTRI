# Runbook Superadmin AKRAB

Status awal: fitur superadmin wajib tetap `OFF` sampai seluruh checkpoint program
superadmin dinyatakan GO.

## Batas operasi

- Jalankan hanya pada database dan web root AKRAB.
- Jangan memakai kredensial dari domain atau proyek lain.
- Jangan menyimpan password superadmin di source, shell history, tiket, atau log.
- Jangan mengaktifkan fitur clinical sebagai bagian dari provisioning.
- Provisioning tidak membuat role lain dan menolak superadmin kedua.

## Prasyarat

1. Backup database AKRAB sudah tersedia dan dapat dibaca.
2. Migration `008_superadmin_identity` sudah direhearsal pada clone database.
3. Akun migration database memiliki hak schema hanya selama proses migration.
4. `AKRAB_SUPERADMIN_ENABLED=false`.
5. Nama dan username superadmin telah disetujui pemilik sistem.

## Rehearsal migration

Jalankan pada clone atau database staging AKRAB, bukan langsung pada produksi:

```powershell
php tools/migrate.php
php tools/migrate.php
```

Eksekusi kedua harus melaporkan schema sudah current. Verifikasi bahwa:

- role lama tetap tersedia;
- kolom `status` bernilai `active` untuk akun existing;
- hanya satu row dengan role `superadmin` yang dapat dibuat;
- login tiga role lama tetap lulus.

## Provisioning atau recovery

Masukkan password kuat melalui environment proses yang terlindungi. Nilai contoh
`replace_before_running_cli_tool` sengaja ditolak.

```powershell
$env:AKRAB_PROVISION_SUPERADMIN_PASSWORD = '<secret-sementara>'
php tools/provision_superadmin.php --username=<username> --name="<nama>"
Remove-Item Env:AKRAB_PROVISION_SUPERADMIN_PASSWORD
```

Perintah dengan username yang sama memulihkan akun singleton: nama dan password
diperbarui serta status menjadi `active`. Username berbeda ditolak apabila
superadmin sudah ada. Output tidak pernah menampilkan password.

Setelah selesai:

1. Pastikan hanya satu akun `superadmin` ada.
2. Pastikan audit `superadmin.provisioned` atau `superadmin.recovered` tercatat.
3. Hapus secret sementara dari environment proses.
4. Pertahankan `AKRAB_SUPERADMIN_ENABLED=false` sampai dashboard dan seluruh gate
   keamanan selesai.

## Aktivasi dan rollback aman

Aktivasi hanya boleh dilakukan setelah checkpoint release final mendapat GO.
Rollback pertama selalu mengubah `AKRAB_SUPERADMIN_ENABLED=false`; ini memblokir
login superadmin tanpa menghapus akun atau audit. Jangan melakukan hard delete
terhadap akun singleton.

Jika provisioning gagal, jangan mencetak exception atau credential. Periksa
koneksi database AKRAB, status migration, argumen CLI, dan environment terlindungi,
lalu ulangi. Jangan mengalihkan perintah ke database domain lain.
