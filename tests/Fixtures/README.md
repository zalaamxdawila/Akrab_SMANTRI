# Database Test Fixtures

Fixture ini hanya untuk database test terisolasi. Jangan pernah menjalankannya pada database produksi.

Urutan yang direncanakan untuk integration test:

1. Buat database kosong khusus test.
2. Jalankan migration/schema yang sedang diuji.
3. Jalankan `seed.sql`.
4. Jalankan test.
5. Hapus database test.

Semua identitas dalam fixture bersifat sintetis.
