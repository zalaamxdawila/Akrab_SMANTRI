# AKRAB — Aplikasi Kesehatan Remaja Bebas Anemia

[![Produksi](https://img.shields.io/badge/Produksi-akrab.portodq.com-047857)](https://akrab.portodq.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php)](https://www.php.net/)
[![Android](https://img.shields.io/badge/Android-7.0%2B-3DDC84?logo=android)](https://akrab.portodq.com/downloads/AKRAB-Android-v1.0.0.apk)

AKRAB adalah aplikasi web, PWA, dan Android untuk skrining awal risiko anemia serta pendampingan kesehatan remaja di sekolah. Alur skrining utama menggunakan gejala dan faktor risiko tanpa mewajibkan pemeriksaan Hb.

> [!IMPORTANT]
> AKRAB memberikan hasil skrining risiko/indikasi, bukan diagnosis anemia dan bukan pengganti pemeriksaan tenaga kesehatan. Instrumen, ambang, redaksi hasil, dan jalur rujukan dikelola bersama ahli medis sekolah terkait. AKRAB tidak menyatakan kerja sama, afiliasi, validasi, atau dukungan WHO maupun organisasi eksternal lain.

## Fitur utama

- Skrining bertahap: gejala lebih dahulu, kemudian faktor risiko hanya bagi siswa yang memenuhi ambang.
- Saran tindak lanjut ke UKS, Puskesmas, atau dokter sesuai hasil dan tanda bahaya.
- Biodata minimum sebelum skrining: kelas, tanggal lahir/usia, dan gender.
- Pemantauan konsumsi Tablet Tambah Darah (TTD), menstruasi, edukasi, dan konsultasi siswa–UKS.
- Dashboard UKS, audit, serta ekspor data kuesioner baru dan lama secara terpisah.
- Pengisian ulang kuesioner setelah reset oleh petugas UKS berwenang dengan alasan wajib.
- PWA dan APK Android yang selalu menampilkan konten web produksi terbaru.

## Alur skrining tanpa Hb

```text
Login siswa
  └─ biodata belum lengkap → lengkapi kelas, tanggal lahir/usia, dan gender
      └─ tahap gejala: 10 pertanyaan, skala 0–10
          ├─ rerata <= 4,6 → hasil dan penjelasan gejala; faktor risiko terkunci
          └─ rerata > 4,6 → tahap faktor risiko
              ├─ skor < 75% → terindikasi risiko anemia + saran tindak lanjut
              └─ skor >= 75% → tidak terindikasi pada aturan ini + saran pemantauan
```

Semua skor dan pembatas tahap dihitung di server. JavaScript hanya membantu pengalaman antarmuka dan bukan kontrol akses.

Aturan aktif:

- Skor gejala = rata-rata 10 jawaban.
- Faktor risiko terbuka hanya jika skor gejala `> 4,6`.
- Skor faktor risiko menggabungkan dimensi menstruasi dan enam kebiasaan makan dengan bobot sama.
- Skor faktor risiko `< 75%` menghasilkan status terindikasi risiko, bukan diagnosis.
- Versi aturan tersimpan bersama hasil: `akrab-school-screening-v1`.
- Pertanyaan bersumber dari [`Kuesioner.pdf`](Kuesioner.pdf); pemetaan dan keterbatasannya dijelaskan di [`docs/specs/staged-symptom-risk-screening.md`](docs/specs/staged-symptom-risk-screening.md).

Data kuesioner dan hasil laboratorium lama tetap dipertahankan sebagai riwayat, tetapi tidak ditafsirkan ulang dan tidak menjadi syarat skrining baru.

## Akun, email, dan pengisian ulang

- Pendaftaran akun baru mewajibkan email valid.
- Akun lama yang belum memiliki email tetap dapat login. Dashboard menampilkan bubble pada ikon profil; pengingat dapat ditutup dan email dapat dilengkapi dari profil.
- Siswa tidak dapat mereset kuesionernya sendiri.
- Petugas UKS berwenang dapat mengaktifkan pengisian ulang dengan alasan reset 5–500 karakter.
- Hasil sebelumnya dipindahkan menjadi riwayat pribadi dan tidak lagi dihitung sebagai data utama.
- Hasil baru menjadi data utama berikutnya. Ekspor hasil baru dan hasil lama memakai berkas serta struktur yang berbeda.

## Instalasi aplikasi

### Android APK

Unduh APK resmi dari:

- [AKRAB Android v1.0.0](https://akrab.portodq.com/downloads/AKRAB-Android-v1.0.0.apk)
- [Checksum SHA-256](https://akrab.portodq.com/downloads/AKRAB-Android-v1.0.0.apk.sha256)
- [Unduh APK langsung dari GitHub](https://github.com/zalaamxdawila/Akrab_SMANTRI/raw/refs/heads/main/downloads/AKRAB-Android-v1.0.0.apk)

Identitas rilis:

| Properti | Nilai |
|---|---|
| Package ID | `com.portodq.akrab` |
| Versi | `1.0.0` (`versionCode 10000`) |
| Minimum Android | Android 7.0 / API 24 |
| Target SDK | API 36 |
| SHA-256 APK | `c82beefcd52f21261f88cac2568566e6c4c2fe6bfb74f3714d8212eed1d3e099` |
| SHA-256 sertifikat | `ad3b72bfba04fa598295a90ba4a4ea8b49ead2fe770a38811d6407b5974b656e` |

APK merupakan wrapper Cordova tipis yang hanya memuat `https://akrab.portodq.com/`. Perubahan konten web langsung tampil di aplikasi tanpa membuat APK baru. Build baru hanya diperlukan ketika package, ikon, konfigurasi native, SDK, permission, atau identitas rilis berubah.

Konfigurasi Android membatasi navigasi ke origin HTTPS AKRAB, menonaktifkan cleartext HTTP, backup aplikasi, insecure file mode, dan WebView inspector, serta tidak memasang plugin Cordova. Manifest final hanya meminta izin internet dan permission internal AndroidX.

Karena APK dipasang langsung dari website dan belum didistribusikan melalui Google Play, Android dapat meminta izin “instal aplikasi tidak dikenal” dan Play Protect dapat menampilkan pemeriksaan tambahan.

### PWA

Buka [https://akrab.portodq.com/](https://akrab.portodq.com/) di Chrome/Edge Android atau desktop, lalu pilih **Pasang Aplikasi**. Di Safari iPhone/iPad, gunakan **Bagikan → Tambahkan ke Layar Utama**.

Service Worker hanya menyimpan aset statis same-origin yang diizinkan. Navigasi, halaman akun, dan halaman data kesehatan tidak dimasukkan ke cache aplikasi.

## Tech stack

| Layer | Teknologi |
|---|---|
| Frontend | HTML5, CSS3, JavaScript, Bootstrap 5, Chart.js, Lucide |
| Backend | PHP 8.2+ native, server-rendered pages, session authentication |
| Database | MySQL/MariaDB, PDO, migrasi additive/versioned |
| Security | CSRF, role/ownership checks, session hardening, rate limiting, audit log, output encoding |
| PWA | Web App Manifest dan Service Worker |
| Android | Cordova CLI 13.0.0, cordova-android 15.1.0, JDK 17, SDK 36 |
| Quality | PHPUnit 12, PHP lint, Composer audit, npm audit, Android lint/apksigner |

## Setup web lokal

Persyaratan: PHP 8.2+, MySQL/MariaDB, Composer, dan HTTPS untuk staging/produksi.

```bash
git clone https://github.com/zalaamxdawila/Akrab_SMANTRI.git
cd Akrab_SMANTRI
cp .env.example .env
composer install
php tools/migrate.php
```

Konfigurasi lokal minimum:

```env
AKRAB_APP_ENV=development
CLINICAL_RISK_ENABLED=false
AKRAB_RESEARCH_MODEL_ENABLED=false
```

Jangan commit `.env`, dump database, log, data siswa, backup produksi, keystore, atau password signing.

## Build Android

Proyek Cordova berada di [`mobile/`](mobile/README.md).

```powershell
cd mobile
npm install
$env:CORDOVA_JAVA_HOME='C:\Program Files\Java\jdk-17.0.9'
$env:ANDROID_HOME='C:\Users\<user>\AppData\Local\Android\Sdk'
npm run android:check
npm run android:release
```

`npm run android:release` menghasilkan APK rilis unsigned. APK wajib di-zipalign, ditandatangani dengan identitas rilis yang sama, lalu diverifikasi memakai `apksigner` sebelum dipublikasikan. Folder `mobile/.signing/` bersifat lokal, diabaikan Git, dan harus dicadangkan secara aman; kehilangan key akan mencegah pembaruan aplikasi terpasang dengan package ID yang sama.

Dokumentasi acuan: [Cordova Android](https://cordova.apache.org/docs/en/latest/guide/platforms/android/), [Cordova allowlist](https://cordova.apache.org/docs/en/latest/guide/appdev/allowlist/), dan [Android app signing](https://developer.android.com/studio/publish/app-signing).

## Perintah kualitas

| Perintah | Fungsi |
|---|---|
| `composer quality` | Menjalankan quality gate proyek |
| `php vendor/bin/phpunit --testsuite Unit` | Menjalankan unit test |
| `php tools/lint.php` | Memeriksa sintaks seluruh PHP |
| `php tools/preflight.php` | Memeriksa kesiapan environment tanpa menampilkan secret |
| `php tools/migrate.php status` | Melihat status migrasi |
| `php tools/migrate.php --allow-production` | Menjalankan migrasi produksi dengan persetujuan eksplisit |
| `cd mobile && npm audit` | Mengaudit dependency Cordova |

## Struktur direktori

```text
Akrab_SMANTRI/
├── app/                  # Service, repository, policy, dan aturan domain
├── assets/               # CSS, JavaScript, ikon, dan vendor frontend lokal
├── config/               # Konfigurasi aplikasi, keamanan, dan validasi
├── database/             # Skema dan migrasi versioned
├── docs/                 # Spesifikasi klinis/produk dan runbook operasional
├── mobile/               # Sumber wrapper Cordova Android
├── orangtua/             # Portal orang tua/wali
├── siswa/                # Portal siswa dan alur skrining
├── tests/                # Unit, integration, fixture, dan browser test
├── tools/                # Migrasi, lint, preflight, dan utilitas rilis
└── uks/                  # Portal petugas UKS
```

## Produksi dan deployment

Semua role menggunakan origin produksi resmi [https://akrab.portodq.com/](https://akrab.portodq.com/). Aplikasi berada di `public_html/akrab` pada akun hosting bersama; deployment tidak boleh menyalin, mengubah, atau menghapus proyek/domain lain.

Setiap deployment harus membuat backup, memakai allowlist file rilis, menjalankan migrasi yang diperlukan, memeriksa header keamanan dan route terproteksi, lalu menyediakan rollback. Lihat:

- [Deployment & rollback](docs/operations/deployment-runbook.md)
- [Backup & restore](docs/operations/backup-restore.md)
- [Incident & rotasi secret](docs/operations/incident-secret-rotation.md)
- [Retensi data](docs/operations/data-retention.md)
- [Release checklist](docs/operations/release-checklist.md)

---

© 2026 BIOCORE SYSTEM TEAM — AKRAB SMAN 3 Padang
