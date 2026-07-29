# Spec: Superadmin Master Dashboard dan Login As

Status: DRAFT — menunggu persetujuan
Target aplikasi: AKRAB
Target produksi: `https://akrab.portodq.com/`

## Objective

Menambahkan tepat satu akun `superadmin` sebagai operator master AKRAB.
Superadmin memiliki dashboard terpusat untuk mengawasi dan mengelola seluruh
modul bisnis yang saat ini ada, tanpa memberikan akses ke secret, password
tersimpan, konfigurasi server mentah, atau aktivasi fitur klinis yang belum
melewati gate.

Dashboard harus mendukung:

- ringkasan kesehatan sistem dan metrik operasional;
- pencarian, peninjauan, koreksi, penonaktifan, dan pengarsipan data;
- pengelolaan akun siswa, UKS, dan orang tua;
- pengelolaan relasi orang tua–siswa;
- peninjauan data kesehatan, TTD, menstruasi, konsultasi, artikel, notifikasi,
  dan laporan;
- peninjauan audit trail;
- `Login As` ke akun siswa, UKS, atau orang tua.

Tidak ada hard delete dari dashboard. Semua mutasi sensitif membutuhkan
konfirmasi, alasan, CSRF yang valid, otorisasi server-side, dan audit.

## Actors and Authorization

### Superadmin

- Hanya ada satu akun superadmin di seluruh sistem.
- Akun dibuat atau dipulihkan melalui command CLI, bukan registrasi web.
- Akun tidak dapat dihapus, dinonaktifkan, diarsipkan, atau diubah rolenya dari
  dashboard.
- Superadmin biasa dapat membaca dan mengelola data aplikasi sesuai allowlist
  aksi per modul.
- Dashboard bukan editor SQL atau file manager.

### Existing roles

Role `siswa`, `uks`, dan `orangtua` mempertahankan kewenangan dan dashboard
masing-masing. Penambahan superadmin tidak boleh memperluas akses role lama.

### Clinical boundary

Superadmin hanya dapat melihat status feature flag klinis. Superadmin tidak
dapat mengaktifkan model risiko atau mengubah approval gate. Aktivasi tetap
memerlukan clinical owner dan CP-07 GREEN.

## Login As Contract

Login As memakai dua identitas terpisah:

- `authenticated actor`: superadmin yang benar-benar login;
- `effective actor`: siswa, UKS, atau orang tua yang sedang ditiru.

Implementasi tidak boleh menimpa atau kehilangan identitas superadmin asli.

### Starting a session

- Hanya dapat dimulai dari session superadmin asli.
- Memerlukan password superadmin kembali (step-up authentication).
- Memerlukan alasan terstruktur dan catatan singkat tervalidasi.
- Target harus aktif dan memiliki role `siswa`, `uks`, atau `orangtua`.
- Session ID diregenerasi saat mulai dan selesai.
- Masa berlaku default 15 menit dan tidak dapat diperpanjang diam-diam.
- Hanya satu impersonation session aktif per browser session.

### During a session

- Setiap halaman menampilkan banner permanen berisi role/nama target, sisa
  waktu, dan tombol `Kembali ke Superadmin`.
- Mutasi operasional normal milik target diperbolehkan.
- Setiap request mutasi menyimpan authenticated actor, effective actor,
  impersonation session ID, reason category, request ID, route, outcome, dan
  waktu.
- Halaman superadmin tidak dapat diakses selama effective actor bukan
  superadmin.

### Always blocked during Login As

- mengganti username atau password;
- mengubah role atau status akun;
- membuat, mengubah, atau memulihkan superadmin;
- melakukan ekspor massal;
- mengarsipkan atau menghapus data;
- mengubah konfigurasi aplikasi atau server;
- mengaktifkan atau mengubah gate klinis;
- memulai Login As bertingkat.

### Ending a session

- Berakhir melalui POST ber-CSRF, expiry, logout, atau invalidation session.
- Identitas superadmin dipulihkan dari server-side session state.
- Session ID diregenerasi.
- Event selesai/expired dicatat dalam audit.
- Tombol Back atau request lama tidak boleh menghidupkan kembali impersonation.

## Data Model

Migration versioned berikut diperlukan:

1. Tambahkan `superadmin` ke enum role `users`.
2. Tambahkan constraint berbasis generated column/unique key yang menjamin
   maksimal satu row ber-role `superadmin`.
3. Tambahkan status akun yang mendukung `active`, `inactive`, dan `archived`
   tanpa hard delete.
4. Buat tabel `impersonation_sessions` yang menyimpan superadmin, target,
   reason category, catatan tervalidasi, waktu mulai/expiry/selesai, dan status.
5. Perluas audit agar authenticated actor dan effective actor dapat dibedakan
   tanpa menyimpan credential atau session token mentah.
6. Tambahkan metadata arsip/koreksi per domain secara bertahap hanya pada tabel
   yang dikelola sprint terkait.

Migration harus:

- aman untuk database yang sudah berjalan;
- idempotent melalui `schema_migrations`;
- kompatibel dengan MariaDB Hostinger;
- mempertahankan semua foreign key dan data existing;
- tidak membuat akun superadmin otomatis.

## Functional Modules

### Overview

- jumlah akun aktif/nonaktif per role;
- jumlah relasi orang tua pending/approved/rejected;
- ringkasan konsultasi, artikel, TTD, dan data kesehatan;
- health status, migration version, feature flag klinis read-only;
- aktivitas audit terbaru tanpa menampilkan data sensitif.

### User management

- daftar dengan pencarian, filter role/status, pagination, dan detail;
- buat akun siswa, UKS, dan orang tua;
- koreksi field allowlisted;
- aktifkan/nonaktifkan/arsipkan dengan alasan;
- tidak menampilkan `password_hash`;
- tidak mengubah akun menjadi/dari superadmin;
- perubahan role terhadap akun yang sudah memiliki data tidak dilakukan
  langsung; operator harus memakai workflow migrasi data terpisah.

Pemulihan credential superadmin dan pengguna tetap melalui CLI sampai tersedia
kanal reset password yang aman. Dashboard tidak menampilkan atau menghasilkan
password.

### Parent–student links

- tinjau, approve, reject, koreksi, dan arsipkan relasi;
- verifikasi role/target server-side;
- setiap keputusan menyimpan actor dan alasan.

### Health and operational records

- view, filter, dan koreksi field allowlisted;
- arsipkan dengan alasan, tanpa hard delete;
- tampilkan riwayat koreksi;
- jangan menghitung data terarsip pada agregat operasional;
- jangan menghasilkan diagnosis baru atau mengaktifkan model klinis.

Cakupan data: kuesioner, hasil deteksi existing, kadar Hb, konsumsi TTD,
riwayat menstruasi, jadwal/log notifikasi, dan saran edukasi.

### Consultations and education

- tinjau konsultasi dan balasan;
- koreksi status yang tidak konsisten melalui aksi terdefinisi;
- arsipkan thread/artikel dengan alasan;
- kelola artikel dan saran edukasi tanpa melewati ownership secara tidak
  tercatat.

### Reports and audit

- laporan agregat tidak menampilkan secret atau password hash;
- ekspor massal hanya dari session superadmin asli, tidak saat Login As;
- ekspor membutuhkan konfirmasi dan audit;
- audit dapat difilter berdasarkan waktu, actor, effective actor, action,
  target, outcome, dan request ID;
- audit bersifat append-only dari antarmuka aplikasi.

## Threat Model and Abuse Cases

| Threat | Control |
|---|---|
| Pengguna mengubah session role menjadi superadmin | Otorisasi server-side dan lookup akun aktif |
| Superadmin menyangkal aksi Login As | Audit dua identitas dan request ID |
| Session fixation | Regenerasi session ID pada login, mulai, dan selesai Login As |
| CSRF aksi master | POST-only, CSRF token, dan PRG |
| Login As dipakai mengubah credential | Guard aksi kritis terpusat dan test route coverage |
| Impersonation kedaluwarsa tetap aktif | Expiry diperiksa pada setiap request |
| Nested impersonation | Ditolak server-side |
| IDOR pada target/data | Query terparameterisasi dan policy per target |
| Audit menyimpan PII/secret | Metadata allowlist dan redaction |
| Hard delete tidak sengaja | Tidak ada endpoint DELETE/hard-delete di dashboard |
| Fitur klinis diaktifkan admin | Gate klinis tetap di luar permission superadmin |
| Akun superadmin kedua dibuat | Unique constraint database dan provisioning idempotent |

## Tech Stack

- PHP 8.2 server-rendered application;
- MariaDB/MySQL melalui PDO;
- Bootstrap 5 dan asset frontend existing;
- session cookie server-side;
- PHPUnit 12 untuk unit/integration tests;
- Selenium/Chrome terisolasi untuk browser verification;
- migration PHP versioned di `database/migrations`.

Tidak menambahkan framework atau dependency runtime baru tanpa persetujuan.

## Commands

```powershell
# Lint semua PHP
php tools\lint.php

# Unit/integration suite
vendor\bin\phpunit --colors=never

# Focused suite
vendor\bin\phpunit --colors=never --filter Superadmin
vendor\bin\phpunit --colors=never --filter Impersonation

# Model regression yang tetap wajib dijaga
python -m pytest

# Migration
php tools\migrate.php

# Provisioning (interface yang akan dibuat)
php tools\provision_superadmin.php
```

Catatan lingkungan: PHPUnit lokal Windows saat ini terblokir karena ekstensi
`mbstring` tidak tersedia. Sprint tidak boleh dianggap selesai hanya dengan
lint; test harus dijalankan pada runtime PHP yang memiliki extension wajib
sebelum deployment.

## Project Structure

```text
superadmin/                         dashboard dan route master
app/Repositories/Superadmin*/       query read/write terparameterisasi
app/Services/Superadmin*/           use case dan transaksi
app/Security/ImpersonationService.php
config/authorization.php            role/action policy kanonis
config/session.php                  lifecycle session aman
config/observability.php            audit dua identitas
database/migrations/                perubahan schema versioned
tools/provision_superadmin.php      provisioning CLI
tests/Unit/                         policy dan guard tests
tests/Integration/                  migration/repository/audit tests
docs/                               runbook dan UAT evidence
```

## Code Style

- `declare(strict_types=1)` pada file service, repository, policy, dan tool baru.
- Query harus prepared/parameterized.
- Route tipis: validasi → authorization → service → redirect/render.
- Semua output HTML memakai `escape_output`.
- Mutasi memakai transaksi dan Post/Redirect/Get.
- Action permission memakai nama eksplisit, bukan wildcard `*`.

Contoh:

```php
requireSuperadmin();
requirePostWithCsrf();

$command = validateArchiveCommand($_POST);
$service->archiveUser(
    actor: currentAuthenticatedActor(),
    targetId: $command['target_id'],
    reason: $command['reason'],
);
```

## Testing Strategy

### Unit

- role/dashboard mapping dan permission matrix;
- satu-superadmin invariant;
- validation alasan, target, status, dan expiry;
- daftar aksi yang diblokir selama Login As;
- session actor/effective-actor transitions;
- audit redaction dan metadata allowlist;
- route coverage untuk seluruh mutasi sensitif.

### Integration

- migration existing database dan idempotent rerun;
- provisioning pertama dan penolakan superadmin kedua;
- repository pagination/filter/ownership;
- start/end/expire Login As;
- audit dua identitas untuk mutasi berhasil dan gagal;
- transaksi koreksi/arsip rollback saat gagal.

### Browser

- login superadmin dan dashboard;
- keyboard/accessibility dan responsive layout;
- Login As untuk tiga role;
- banner dan countdown terlihat pada setiap halaman;
- mutasi operasional diperbolehkan;
- aksi kritis diblokir;
- kembali ke superadmin memulihkan identitas;
- tidak ada console/network error.

### Security regression

- CSRF, session fixation, nested impersonation, expiry bypass;
- horizontal/vertical privilege escalation;
- forged target ID dan forged role;
- direct URL access ke halaman superadmin;
- audit actor/effective actor tidak dapat dipalsukan dari request.

## Boundaries

### Always

- pertahankan clinical feature OFF;
- gunakan feature flag untuk dashboard superadmin sampai UAT lulus;
- gunakan migration versioned dan backup sebelum production migration;
- audit semua aksi sensitif;
- tampilkan konfirmasi dan minta alasan;
- paginate seluruh daftar;
- uji session normal dan Login As.

### Ask first

- deployment atau migration produksi;
- perubahan struktur data yang destructive;
- penambahan dependency;
- perubahan CSP, cookie, atau runtime hosting;
- perluasan aksi Login As di luar allowlist spec.

### Never

- menyimpan atau menampilkan password/secret;
- membuat registrasi superadmin publik;
- hard delete dari dashboard;
- mengizinkan wildcard permission;
- mengaktifkan fitur klinis melalui dashboard;
- menyentuh domain/project selain AKRAB;
- menganggap `$_SESSION['role']` saja sebagai bukti superadmin tanpa validasi
  akun aktif.

## Success Criteria

- Tepat satu superadmin dapat diprovision dan login.
- Role lama tetap tidak dapat mengakses route superadmin.
- Dashboard memberi akses terpusat ke seluruh modul existing sesuai policy.
- Semua list terpaginate dan semua mutasi tervalidasi, ber-CSRF, transaksional,
  serta diaudit.
- Tidak ada hard delete atau password/secret disclosure.
- Login As berhasil untuk ketiga role, berakhir maksimal 15 menit, menampilkan
  banner, dan selalu mempertahankan authenticated actor.
- Mutasi operasional saat Login As tercatat atas dua identitas.
- Semua aksi kritis Login As pada spec ditolak server-side.
- Clinical feature tetap OFF.
- Migration fresh/existing/idempotent, lint, unit, integration, security,
  browser UAT, dan rollback drill lulus.
- CP baru hanya GREEN setelah review manusia dan bukti UAT.

## Out of Scope

- role admin biasa atau multi-superadmin;
- raw SQL editor, file manager, secret manager, atau hosting control panel;
- hard delete;
- aktivasi/konfigurasi model klinis;
- proses bisnis baru di luar modul AKRAB saat ini;
- password reset berbasis email/SMS sebelum kanal delivery aman tersedia.

## Open Questions

Tidak ada open question produk. Detail field allowlist dan route matrix akan
diturunkan dari source existing pada fase planning dan direview sebelum
implementasi.
