# Incident Response and Secret Rotation

## Severity

- SEV-1: kebocoran data/secret, akses lintas role, korupsi data, atau aplikasi tidak tersedia luas.
- SEV-2: critical flow terganggu tanpa bukti kebocoran atau workaround terbatas tersedia.
- SEV-3: degradasi minor yang tidak mengancam data atau critical flow.

## First response

1. Tetapkan incident commander dan scribe.
2. Catat waktu, gejala, release, correlation ID, dan scope tanpa menyalin PII ke tiket.
3. Contain: matikan clinical flag, nonaktifkan akun/cron terkait, atau rollback release sesuai dampak.
4. Pertahankan log dan snapshot; jangan menghapus bukti.
5. Beri tahu data owner/security owner untuk SEV-1.

## Database credential rotation

1. Buat kredensial runtime baru dengan hak minimum pada secret manager/panel hosting.
2. Uji pada staging/clone.
3. Perbarui environment produksi dan restart/switch release bila diperlukan.
4. Verifikasi health dan critical flow.
5. Cabut kredensial lama dan cari penggunaannya pada cron/integrasi.
6. Rotasi akun migrasi secara terpisah; jangan menyamakan runtime dan migration credential.

Password database yang pernah dibagikan melalui percakapan harus dianggap terpapar dan dirotasi setelah deployment stabil.

## Closure

Incident ditutup setelah containment, recovery, komunikasi, rotasi, dan post-incident review selesai. Review harus menghasilkan owner dan tenggat untuk setiap tindakan; jangan menaruh data kesehatan rinci dalam laporan umum.
