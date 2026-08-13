# AKRAB (Akrab_SMANTRI)
> **Aplikasi Kesehatan Remaja Bebas Anemia** — Pemantauan TTD, kuesioner digital, simulasi regresi logistik, dan tata kelola kesehatan sekolah

[![Repository](https://img.shields.io/badge/GitHub-Akrab__SMANTRI-blue?logo=github)](https://github.com/zalaamxdawila/Akrab_SMANTRI)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)](https://php.net)
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-4479A1?logo=mysql)](https://mysql.com)

---

## 📌 Ringkasan

**AKRAB** (`Akrab_SMANTRI`) adalah aplikasi berbasis web & PWA (Progressive Web App) yang dirancang untuk mendukung program pencegahan anemia remaja di lingkungan sekolah. Sistem ini memfasilitasi:

1. **Pencatatan & Pemantauan TTD**: Integrasi pencatatan konsumsi Tablet Tambah Darah (TTD) mingguan siswa.
2. **Kuesioner Digital & Simulasi Regresi Logistik**: Perhitungan transparan dari Hb, MCH, MCHC, dan MCV yang dikendalikan feature flag (*fail-closed*).
3. **Konsultasi Siswa – UKS**: Layanan komunikasi interaktif antara siswa dan pembina UKS/Tenaga Kesehatan.
4. **Portal Orang Tua / Wali**: Akses terverifikasi untuk memantau perkembangan kesehatan remaja.
5. **Dashboard Analytics UKS & Superadmin**: Visualisasi tren kepatuhan TTD, ekspor laporan CSV/Excel, dan audit log komprehensif.
6. **Onboarding Siswa Terarah**: Setelah login, siswa diarahkan berurutan untuk melengkapi email, kuesioner, dan data laboratorium yang belum lengkap.
7. **Pemulihan Password Berbasis Email**: Permintaan dapat diajukan menggunakan email atau Username/NISN dengan token reset yang disimpan dalam bentuk hash dan memiliki masa berlaku.

---

## 🧭 Alur Onboarding Siswa

Untuk memastikan hasil kuesioner dapat diproses secara lengkap, aplikasi menerapkan urutan berikut:

```text
Login
  └─ Email belum tersedia → Lengkapi email
       └─ Kuesioner belum tersedia → Isi kuesioner
            └─ Hb/MCHC/MCV/MCH belum lengkap → Lengkapi data laboratorium
                 └─ Data lengkap → Dashboard dan hasil kuesioner
```

- Pengisian data laboratorium pertama dapat dilakukan langsung oleh siswa.
- Hb, MCHC, MCV, dan MCH wajib diisi bersamaan.
- Setelah tersimpan, siswa tidak dapat mengubah data secara langsung.
- Perubahan berikutnya harus diajukan dan disetujui oleh UKS atau Superadmin.
- UKS dan Superadmin tidak terkena redirect onboarding siswa.

---

## 📊 Simulasi Regresi Logistik

Model penelitian aktif menggunakan formula terpusat berikut:

```text
z = 15,5 − 1,5(Hb)
         − 0,1(MCH − 29,5)
         − 0,1(MCHC − 33,2)
         − 0,05(MCV − 90)

P = 1 / (1 + e⁻ᶻ)
```

Kategori simulasi:

| Probabilitas | Kategori |
|---:|---|
| `< 33%` | Rendah |
| `33% – < 66%` | Sedang |
| `≥ 66%` | Tinggi |

Halaman hasil menjelaskan nilai input, nilai acuan, nilai terpusat, koefisien, kontribusi setiap variabel, nilai linear `z`, fungsi sigmoid, probabilitas, dan kategori. Versi formula saat ini adalah `AKRAB-RESEARCH-CENTERED-v1.1`.

> [!IMPORTANT]
> Hasil merupakan **simulasi model penelitian**, bukan diagnosis medis, rekomendasi pengobatan, atau pengganti pemeriksaan tenaga kesehatan. Formula dan batas kategori belum dinyatakan sebagai model klinis tervalidasi.

---

## 🛠️ Tech Stack

| Layer | Teknologi | Deskripsi |
|---|---|---|
| **Frontend** | HTML5, CSS3, JavaScript (Vanilla), Bootstrap 5, Chart.js, SweetAlert2 | UI responsive, lightweight, siap PWA (`manifest.json` & Service Worker) |
| **Backend** | PHP 8.2+ Native (MVC Architecture) | Fast, robust, aman, support session & `password_hash()` bcrypt |
| **Database** | MySQL / MariaDB (InnoDB, JSON support) | Penyimpanan data relasional dan audit trail |
| **Penerbitan/Build** | Composer | Dependency & Quality tooling (`phpstan`, `phpunit`) |
| **Autentikasi & Security** | Session hardening, WebAuthn/Passkey, CSRF, rate limiting, token reset ter-hash | Perlindungan akun dan data kesehatan sensitif |

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
   AKRAB_RESEARCH_MODEL_ENABLED=false
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
| `php tools/lint.php` | Memeriksa sintaks seluruh file PHP |
| `php tools/preflight.php` | Memeriksa kesiapan environment tanpa menampilkan secret |
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
- 📊 [Spesifikasi Simulasi Regresi Logistik](docs/specs/research-logistic-regression.md)
- 🧪 [Pipeline Pelatihan Model](docs/model-training.md)
- 🗂️ [Model Card](docs/model-card.md)

---

## 🌐 Produksi & Environment

Seluruh role pengguna (Siswa, UKS, Orang Tua, Superadmin) menggunakan origin produksi resmi:  
🔗 **[https://akrab.portodq.com/](https://akrab.portodq.com/)**

Deployment produksi harus menggunakan paket allowlist dari `deployment/include.txt`, membuat backup terlebih dahulu, menjalankan migrasi dengan persetujuan eksplisit, dan memverifikasi endpoint `health.php`. Jangan menyalin atau menghapus direktori proyek/domain lain pada akun hosting yang sama.

---

© 2026 BIOCORE SYSTEM TEAM — **Akrab_SMANTRI**
