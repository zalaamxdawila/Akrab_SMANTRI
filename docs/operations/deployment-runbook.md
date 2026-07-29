# Deployment, Migration, and Rollback Runbook

## Preconditions

- Deploy owner dan rollback owner sedang tersedia.
- Release commit/tag, paket allowlist, checksum, dan release sebelumnya tercatat.
- Backup file serta database selesai dan dapat dibaca.
- Environment hosting tersedia tanpa secret di paket.
- Clinical feature tetap OFF kecuali seluruh approval metadata valid.

## Build release

1. Checkout commit release yang sudah disetujui.
2. Bangun paket hanya dari `deployment/include.txt`, lalu terapkan `deployment/exclude.txt`.
3. Verifikasi paket tidak berisi `.git`, `.env`, test, docs internal, ZIP, dump, atau log.
4. Jalankan PHP lint dan secret scan terhadap paket final.
5. Catat SHA-256 paket dan nama release berbasis timestamp/tag.

## Database migration

1. Dry-run pada clone database dengan versi PHP/MySQL hosting.
2. Catat output migration dan perbandingan schema.
3. Ambil backup produksi final.
4. Gunakan akun migrasi terpisah dan jalankan `php tools/migrate.php --allow-production`.
5. Cabut/nonaktifkan kredensial migrasi setelah selesai; runtime account tidak boleh memiliki hak DDL.

## Release switch

1. Upload ke direktori release baru, bukan menimpa release aktif.
2. Suntikkan environment melalui panel/server config.
3. Verifikasi permission: file read-only untuk web user kecuali lokasi log/session yang dikelola hosting.
4. Alihkan document root/symlink ke release baru secara atomik bila hosting mendukung.
5. Jalankan `/health.php`, login tiga role, konsultasi, kuesioner dengan feature flag sesuai gate, export, dan PWA update checks.
6. Pantau error rate, p95, structured log, audit trail, dan health selama minimal 60 menit.

## Rollback triggers

- Health gagal dua kali berturut-turut.
- Error rate lebih dari dua kali baseline atau p95 naik lebih dari 50%.
- Login/otorisasi critical flow gagal.
- Integritas data, migrasi, atau security header bermasalah.
- Data kesehatan bocor ke cache/log/client.

## Rollback

1. Matikan clinical feature dan hentikan traffic/mutasi bila integritas data terancam.
2. Alihkan document root/symlink ke release sebelumnya.
3. Jangan membalik migrasi destruktif secara spontan; pulihkan database hanya berdasarkan runbook backup dan keputusan data owner.
4. Verifikasi health, login, dan error rate release lama.
5. Catat correlation ID, waktu, operator, alasan, dampak, dan keputusan pemulihan.

Rollback file harus dapat selesai kurang dari 10 menit. Pemulihan database memiliki RTO terpisah pada runbook backup.
