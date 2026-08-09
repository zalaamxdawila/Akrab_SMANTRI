# AKRAB

AKRAB adalah aplikasi PHP untuk pencatatan TTD, skrining anemia yang dikendalikan feature flag, konsultasi siswa–UKS, dan akses wali terverifikasi. Data kesehatan diperlakukan sebagai data sensitif dan fitur risiko klinis bersifat fail-closed.

## Persyaratan

- PHP 8.2+ dengan PDO MySQL, JSON, mbstring, DOM, dan XML.
- MySQL/MariaDB yang mendukung InnoDB dan JSON.
- Composer hanya diperlukan untuk quality tooling lokal/CI.
- HTTPS wajib pada staging dan produksi.

## Setup lokal

1. Salin `.env.example` menjadi `.env` lokal dan isi dengan database development, bukan produksi.
2. Pastikan `AKRAB_APP_ENV=development` dan `CLINICAL_RISK_ENABLED=false`.
3. Jalankan `composer install`.
4. Jalankan migrasi dengan `php tools/migrate.php`.
5. Jalankan web server yang document root-nya menunjuk ke root project.

Jangan commit `.env`, dump database, log, backup, atau data pengguna.

## Perintah

| Perintah | Fungsi |
|---|---|
| `composer lint` | Lint seluruh PHP |
| `composer test` | Unit dan integration test |
| `php tools/migrate.php` | Migrasi non-produksi |
| `php tools/migrate.php --allow-production` | Migrasi produksi dengan persetujuan eksplisit |
| `php cron/purge_audit_log.php` | Hapus audit event yang melewati retensi |

## Arsitektur

- Halaman per role: `siswa/`, `uks/`, `orangtua/`.
- Konfigurasi dan kontrol keamanan: `config/`.
- Business services dan repository: `app/`.
- Migrasi versioned: `database/migrations/`.
- Checkpoint dan roadmap: `tasks/`.
- Kebijakan paket produksi: `deployment/`.

## Operasi

- [Deployment dan rollback](docs/operations/deployment-runbook.md)
- [Backup dan restore](docs/operations/backup-restore.md)
- [Incident dan rotasi secret](docs/operations/incident-secret-rotation.md)
- [Retensi dan hak subjek data](docs/operations/data-retention.md)
- [Release checklist dan ownership](docs/operations/release-checklist.md)

Seluruh role, termasuk superadmin pada `/superadmin/`, menggunakan satu origin produksi: `https://akrab.portodq.com/`. Kredensial database hanya boleh disuntikkan melalui konfigurasi hosting.
