# AKRAB (Akrab_SMANTRI)
> **Aplikasi Kesehatan Remaja Bebas Anemia** — Sistem Pemantauan TTD, Kuesioner Digital, Deteksi Risiko Anemia & Dashboard UKS

[![Repository](https://img.shields.io/badge/GitHub-Akrab__SMANTRI-blue?logo=github)](https://github.com/zalaamxdawila/Akrab_SMANTRI)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)](https://php.net)
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-4479A1?logo=mysql)](https://mysql.com)

---

## 📌 Ringkasan

**AKRAB** (`Akrab_SMANTRI`) adalah aplikasi berbasis web & PWA (Progressive Web App) yang dirancang untuk mendukung program pencegahan anemia remaja di lingkungan sekolah. Sistem ini memfasilitasi:

1. **Pencatatan & Pemantauan TTD**: Integrasi pencatatan konsumsi Tablet Tambah Darah (TTD) mingguan siswa.
2. **Kuesioner Digital & Deteksi Risiko Anemia**: Pemodelan skrining anemia yang aman dan dikendalikan feature flag (*fail-closed*).
3. **Konsultasi Siswa – UKS**: Layanan komunikasi interaktif antara siswa dan pembina UKS/Tenaga Kesehatan.
4. **Portal Orang Tua / Wali**: Akses terverifikasi untuk memantau perkembangan kesehatan remaja.
5. **Dashboard Analytics UKS & Superadmin**: Visualisasi tren kepatuhan TTD, ekspor laporan CSV/Excel, dan audit log komprehensif.

---

## 🛠️ Tech Stack

| Layer | Teknologi | Deskripsi |
|---|---|---|
| **Frontend** | HTML5, CSS3, JavaScript (Vanilla), Bootstrap 5, Chart.js, SweetAlert2 | UI responsive, lightweight, siap PWA (`manifest.json` & Service Worker) |
| **Backend** | PHP 8.2+ Native (MVC Architecture) | Fast, robust, aman, support session & `password_hash()` bcrypt |
| **Database** | MySQL / MariaDB (InnoDB, JSON support) | Penyimpanan data relasional dan audit trail |
| **Penerbitan/Build** | Composer | Dependency & Quality tooling (`phpstan`, `phpunit`) |
| **Autentikasi & Security** | WebAuthn / Passkey, Fail-Closed Risk Gate, CSRF Protection | Perlindungan data kesehatan sensitif |

---

## 🚀 Persyaratan Sistem & Quick Start

### Persyaratan
- **PHP 8.2+** (dengan ekstensi `pdo_mysql`, `json`, `mbstring`, `dom`, `xml`)
- **MySQL / MariaDB** (InnoDB & JSON support)
- **Composer** (untuk quality tooling lokal/CI)
- **HTTPS** (wajib untuk lingkungan Staging & Produksi)

### Setup Lokal

1. **Clone repository**:
   ```bash
   git clone https://github.com/zalaamxdawila/Akrab_SMANTRI.git
   cd Akrab_SMANTRI
   ```
2. **Konfigurasi Environment**:
   Salin file `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   ```
   Pastikan pengaturan lokal:
   ```env
   AKRAB_APP_ENV=development
   CLINICAL_RISK_ENABLED=false
   ```
3. **Install Dependensi & Migrasi**:
   ```bash
   composer install
   php tools/migrate.php
   ```
4. **Jalankan Web Server**:
   Arahkan Document Root web server (Apache/Nginx/XAMPP/Laragon) ke direktori root project.

> [!CAUTION]
> Jangan pernah meng-commit file `.env`, database dump, log runtime, backup, atau data pribadi siswa ke version control.

---

## 📜 Perintah Utama

| Perintah | Fungsi |
|---|---|
| `composer lint` | Running PHPStan / Linter seluruh file PHP |
| `composer test` | Unit test & Integration test suite |
| `php tools/migrate.php` | Menjalankan migrasi database (Development / Staging) |
| `php tools/migrate.php --allow-production` | Menjalankan migrasi produksi dengan persetujuan eksplisit |
| `php cron/purge_audit_log.php` | Otomasi penghapusan audit event yang melewati masa retensi |

---

## 📁 Struktur Direktori

```text
Akrab_SMANTRI/
├── app/                  # Business services, model regresi, repository & core logic
├── config/               # Konfigurasi aplikasi, keamanan, dan error handling
├── database/             # Skema SQL & migrasi versioned (database/migrations/)
├── siswa/                # Portal & dashboard siswa (TTD, kuesioner, konsultasi)
├── uks/                  # Portal & dashboard petugas UKS (rekapitulasi, laporan, artikel)
├── orangtua/             # Portal pemantauan orang tua / wali terverifikasi
├── superadmin/           # Panel manajemen sistem, audit log, & rujukan
├── cron/                 # Script penjadwalan & notifikasi pengingat
├── docs/                 # Dokumentasi operasional, model card, & spesifikasi klinis
├── tools/                # Script utilitas migrasi, preflight, & seeder
└── deployment/           # Kebijakan & paket rilis produksi
```

---

## 📋 Panduan Operasional

Dokumentasi rinci seputar pengoperasian sistem dapat diakses pada:
- 📖 [Deployment & Rollback Runbook](docs/operations/deployment-runbook.md)
- 💾 [Backup & Restore Database](docs/operations/backup-restore.md)
- 🚨 [Incident Management & Rotasi Secret](docs/operations/incident-secret-rotation.md)
- 🔒 [Retensi Data & Hak Subjek Data](docs/operations/data-retention.md)
- ✅ [Release Checklist & Governance](docs/operations/release-checklist.md)

---

## 🌐 Produksi & Environment

Seluruh role pengguna (Siswa, UKS, Orang Tua, Superadmin) menggunakan origin produksi resmi:  
🔗 **[https://akrab.portodq.com/](https://akrab.portodq.com/)**

---

© 2026 BIOCORE SYSTEM TEAM — **Akrab_SMANTRI**
