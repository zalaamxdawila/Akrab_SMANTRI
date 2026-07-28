# Tech Stack Aplikasi AKRAB
### (Aplikasi Kesehatan Remaja Bebas Anemia) — BIOCORE SYSTEM TEAM

Stack ini disusun khusus supaya bisa **di-upload ke hosting biasa** (shared hosting cPanel seperti Niagahoster, Hostinger, Rumahweb, DomaiNesia, IDCloudHost, dll), tanpa perlu VPS, tanpa perlu install Node.js/Python di server, dan tetap mampu menjalankan 3 fitur inti sesuai proposal:

1. Kuesioner kesehatan digital (faktor risiko & gejala anemia)
2. Sistem deteksi risiko anemia berbasis **regresi logistik**
3. Monitoring kepatuhan konsumsi TTD + dashboard UKS (real-time)

---

## 1. Ringkasan Stack (Overview)

| Layer | Teknologi | Alasan |
|---|---|---|
| Frontend | HTML5, CSS3, JavaScript, Bootstrap 5 | Ringan, tidak perlu build tool, langsung jalan di semua hosting |
| Backend | PHP 8.x (native atau CodeIgniter 4) | Hampir semua hosting Indonesia support PHP + MySQL secara default |
| Database | MySQL / MariaDB | Standar di semua shared hosting, ada phpMyAdmin |
| Model Regresi Logistik | Dilatih di Python (offline), diimplementasi ulang di PHP | Shared hosting biasa **tidak bisa** jalankan Python/Flask — jadi model "dipindahkan" jadi rumus matematika di PHP |
| Chart/Visualisasi Dashboard | Chart.js (client-side) | Tidak butuh server tambahan, cukup file JS |
| Notifikasi pengingat TTD | In-app notification + WhatsApp Gateway (Fonnte/Wablas) opsional | WA gateway lebih murah & efektif untuk remaja dibanding push notification native app |
| Autentikasi | PHP session + `password_hash()` (bcrypt) | Aman dan built-in di PHP |
| Hosting | Shared Hosting cPanel (paket PHP+MySQL) | Sesuai anggaran (Rp10.000.000 cukup untuk domain + hosting 1-3 tahun) |
| Version Control | Git + GitHub | Backup source code & kolaborasi tim |
| Dev Environment lokal | XAMPP / Laragon | Simulasi server PHP+MySQL di laptop sebelum upload |

---

## 2. Frontend (Tampilan Aplikasi)

- **HTML5 + CSS3 + JavaScript (vanilla)** — struktur dasar semua halaman
- **Bootstrap 5** (via CDN, tidak perlu install apa pun) — untuk tampilan responsif di HP siswa
- **Chart.js** (via CDN) — grafik kepatuhan konsumsi TTD & tren risiko anemia di dashboard UKS
- **SweetAlert2** — pop-up notifikasi hasil deteksi/reminder yang lebih ramah remaja
- Opsional: bisa dibungkus jadi **PWA (Progressive Web App)** dengan `manifest.json` + Service Worker, supaya bisa "diinstall" ke HP Android layaknya aplikasi, padahal tetap web biasa (tidak butuh Play Store, tidak butuh hosting khusus).

## 3. Backend (Logika Aplikasi)

**Rekomendasi utama: PHP native atau CodeIgniter 4**

| Opsi | Kapan dipakai |
|---|---|
| PHP native (tanpa framework) | Tim baru belajar coding, timeline OPSI singkat (April–Juli 2026) |
| CodeIgniter 4 | Kalau ingin kode lebih rapi (MVC), tetap ringan dan kompatibel hosting biasa |
| Laravel | **Kurang disarankan** untuk hosting murah — butuh Composer/akses shell yang tidak semua paket shared hosting sediakan |

Fungsi backend yang perlu dibuat:
- Form kuesioner → simpan ke database
- Endpoint hitung skor regresi logistik (lihat bagian 5)
- CRUD data siswa & data konsumsi TTD
- Login multi-role: **Siswa** dan **Petugas UKS/Guru Pembina**
- Endpoint untuk dashboard UKS (ambil data real-time dari database)

## 4. Database

**MySQL / MariaDB** (tersedia gratis di semua paket shared hosting + phpMyAdmin untuk kelola data tanpa command line)

Contoh struktur tabel inti:

```sql
users (id, nama, role[siswa/uks], username, password_hash, kelas)
kuesioner (id, user_id, usia, siklus_menstruasi, pengetahuan, sikap, pola_makan, tanggal_isi)
gejala_klinis (id, kuesioner_id, gejala_1, gejala_2, ..., skor_gejala)
hasil_deteksi (id, user_id, probabilitas_risiko, kategori_risiko, tanggal)
konsumsi_ttd (id, user_id, tanggal, status_konsumsi, waktu_input)
kadar_hb (id, user_id, nilai_hb, kategori_anemia, tanggal_periksa)
```

## 5. Implementasi Model Regresi Logistik (bagian paling penting)

Karena hosting biasa **tidak menjalankan Python**, alurnya dua tahap:

**Tahap 1 — Training (offline, di laptop/Google Colab, tidak di server hosting)**
- Gunakan **Python + scikit-learn** (atau R) untuk melatih model regresi logistik dari data kuesioner + kadar Hb yang sudah dikumpulkan.
- Hasil training berupa koefisien (bobot) tiap variabel, misalnya:
  `Z = b0 + b1(usia) + b2(pola_makan) + b3(gejala) + b4(kepatuhan_TTD) + ...`

**Tahap 2 — Deployment (di server/hosting biasa, pakai PHP)**
- Koefisien hasil training dari Tahap 1 dimasukkan sebagai konstanta di PHP.
- Rumus sigmoid dihitung langsung di PHP, tidak butuh library ML apa pun:

```php
function prediksiRisikoAnemia($input, $koefisien) {
    $z = $koefisien['b0'];
    foreach ($input as $key => $value) {
        $z += $koefisien[$key] * $value;
    }
    $probabilitas = 1 / (1 + exp(-$z)); // fungsi sigmoid
    return $probabilitas; // 0 - 1, bisa dikategorikan rendah/sedang/tinggi
}
```

Keuntungan pendekatan ini: **model tetap "regresi logistik" sesuai metodologi penelitian**, tapi eksekusinya sangat ringan dan 100% kompatibel dengan hosting murah — tidak perlu server Python terpisah.

Kalau model perlu di-retrain ulang secara berkala (misalnya tiap semester dengan data baru), tim tinggal ulangi Tahap 1 dan update angka koefisien di file PHP.

## 6. Notifikasi Pengingat Konsumsi TTD

| Opsi | Biaya | Catatan |
|---|---|---|
| In-app notification (badge/alert saat login) | Gratis | Paling sederhana, cukup query dari database |
| Email (PHPMailer + Gmail SMTP gratis) | Gratis | Cocok untuk notifikasi ke guru/petugas UKS |
| WhatsApp Gateway (Fonnte/Wablas) | ± Rp1.000–3.000/hari pakai | Paling efektif untuk remaja, sesuai budget "Biaya Rancangan Aplikasi" |

Detail implementasi ada di **bagian 11** (fitur notifikasi TTD diperluas jadi otomatis terjadwal, tidak hanya sekadar tampil saat login).

## 7. Dashboard UKS

- Halaman khusus role `uks` menampilkan:
  - Grafik kepatuhan konsumsi TTD per siswa/kelas (Chart.js)
  - Daftar siswa berisiko tinggi anemia (hasil regresi logistik, diurutkan)
  - Filter per kelas, tanggal, tingkat risiko
- Dibangun dengan PHP + query MySQL + Chart.js, tanpa dashboard framework tambahan (biar tetap ringan di hosting biasa)

## 8. Keamanan (wajib meski budget kecil)

- Password disimpan pakai `password_hash()` / `password_verify()` — jangan pernah simpan password polos
- Semua query pakai **Prepared Statement (PDO/MySQLi)** — cegah SQL Injection
- Validasi input di frontend **dan** backend
- Aktifkan **SSL/HTTPS gratis** (biasanya sudah termasuk Let's Encrypt di paket hosting) — penting karena ada data kesehatan siswa
- Data kesehatan sensitif → beri akses terbatas, hanya role `uks` yang bisa lihat data semua siswa

## 9. Hosting & Deployment

**Rekomendasi:** Shared Hosting cPanel paket PHP + MySQL (contoh provider lokal: Niagahoster, Hostinger, Rumahweb, DomaiNesia, IDCloudHost)

Alasan cocok untuk stack ini:
- Support PHP 8.x + MySQL secara default, tanpa setup tambahan
- Ada phpMyAdmin (kelola database via browser, tanpa command line)
- Ada File Manager (upload file via browser) — bisa juga pakai FTP (FileZilla)
- SSL gratis otomatis
- Sudah masuk realistis dalam alokasi anggaran "Biaya Rancangan Aplikasi" (Rp10.000.000) untuk domain `.my.id`/`.com` + hosting 1–3 tahun

**Langkah upload ke hosting:**
1. Develop & testing di lokal pakai XAMPP/Laragon
2. Export database dari lokal (`.sql`) → import ke phpMyAdmin hosting
3. Upload seluruh file PHP/HTML/JS via File Manager cPanel atau FTP
4. Sesuaikan file koneksi database (`config.php`) dengan kredensial database hosting
5. Aktifkan SSL (biasanya tinggal klik "AutoSSL" di cPanel)
6. Testing akses via domain

**Untuk tahap prototipe/uji coba awal (gratis dulu sebelum sewa hosting berbayar):**
- InfinityFree atau 000webhost bisa dipakai untuk demo awal (PHP+MySQL gratis, walau ada batasan performa)

## 10. Alur Kerja Tim (disarankan)

1. **Lokal:** semua anggota develop di XAMPP/Laragon masing-masing
2. **Kolaborasi:** simpan kode di GitHub (private repo), tiap anggota push/pull
3. **Training model:** dikerjakan terpisah pakai Google Colab (gratis, tidak butuh install Python di laptop)
4. **Integrasi:** hasil koefisien model dimasukkan manual ke PHP
5. **Deploy:** upload ke hosting saat sudah siap uji coba ke sampel penelitian

## 11. Fitur Baru #1 — Saran Otomatis Berdasarkan Tingkat Anemia

Fitur ini murni **rule-based** (bukan model terpisah), jadi tidak menambah beban server — cukup PHP biasa dengan struktur `if/else` berdasarkan hasil kategori dari regresi logistik + kadar Hb.

**Alur:**
1. Setelah sistem menghitung probabilitas risiko (bagian 5) → dikategorikan: **Tidak Anemia / Ringan / Sedang / Berat** (mengacu nilai ambang batas Hb yang sudah ada di proposal: ≥12; 11,0–11,9; 8,0–10,9; <8 g/dL)
2. Tiap kategori punya template saran yang tersimpan di tabel `saran_edukasi`, bukan hardcode di kode — supaya guru/tim gizi bisa update isi saran tanpa ubah kode program
3. Saran ditampilkan langsung di halaman hasil deteksi siswa

**Struktur tabel tambahan:**
```sql
saran_edukasi (id, kategori_anemia, judul_saran, isi_saran, rekomendasi_makanan, kapan_rujuk_ke_ahli)
```

**Contoh logika PHP:**
```php
function getSaran($kategori, $koneksi) {
    $stmt = $koneksi->prepare("SELECT * FROM saran_edukasi WHERE kategori_anemia = ?");
    $stmt->execute([$kategori]);
    return $stmt->fetch(); // tampilkan isi_saran + rekomendasi_makanan ke siswa
}
```

Untuk kategori **Sedang/Berat**, saran otomatis diarahkan menyertakan tombol **"Konsultasi dengan Ahli"** (lihat bagian 12) supaya siswa tidak hanya baca saran umum tapi juga didorong konsultasi langsung.

## 12. Fitur Baru #2 — Konsultasi dengan Ahli

Karena budget terbatas dan harus jalan di hosting biasa, fitur ini dibuat sebagai **sistem pesan asinkron sederhana** (bukan video call/live chat real-time yang butuh server khusus seperti WebSocket).

**Rekomendasi teknis: forum/pesan tanya-jawab berbasis database, mirip fitur chat sederhana**

Struktur tabel:
```sql
konsultasi (id, siswa_id, ahli_id, pertanyaan, status[menunggu/dijawab], tanggal_kirim)
balasan_konsultasi (id, konsultasi_id, isi_balasan, tanggal_balas)
```

**Alur:**
1. Siswa mengisi form pertanyaan (bisa otomatis terisi ringkasan hasil deteksinya agar ahli tidak perlu tanya ulang)
2. Data masuk ke tabel `konsultasi` dengan status "menunggu"
3. Role **Ahli** (bisa petugas UKS, guru pembina UKS, atau tenaga kesehatan puskesmas mitra) login ke dashboard khusus, melihat daftar pertanyaan, dan membalas lewat form biasa (PHP + MySQL, tanpa library tambahan)
4. Siswa mendapat notifikasi (in-app / WA) saat pertanyaannya sudah dijawab

**Opsional (kalau ingin lebih real-time tanpa keluar dari hosting biasa):**
- Polling AJAX sederhana (JavaScript `setInterval` cek balasan baru tiap beberapa detik) — tetap kompatibel dengan hosting PHP biasa, tanpa perlu WebSocket/Node.js
- Untuk kasus mendesak, sediakan juga tombol **"Hubungi via WhatsApp"** yang langsung `wa.me/nomor_ahli` — tidak butuh coding backend sama sekali, dan realistis untuk skala penelitian OPSI ini

## 13. Fitur Baru #3 — Notifikasi Pengingat Minum TTD (diperluas)

Proposal sudah menyebutkan notifikasi pengingat TTD; berikut versi implementasi konkretnya:

**Struktur tabel tambahan:**
```sql
jadwal_notifikasi (id, siswa_id, jam_pengingat, hari[harian/mingguan/saat_menstruasi], aktif)
log_notifikasi (id, siswa_id, tanggal_kirim, status_terkirim, sudah_dikonfirmasi)
```

**Cara kerja (tanpa perlu server aktif 24 jam, cocok untuk shared hosting):**

Shared hosting tidak bisa menjalankan proses background terus-menerus, jadi dipakai **CRON JOB** — fitur bawaan cPanel yang sudah tersedia gratis di semua paket hosting:

1. Di cPanel, atur **Cron Job** untuk menjalankan file `kirim_notifikasi.php` otomatis, misalnya tiap jam 07:00 dan 16:00
2. Script PHP tersebut mengecek tabel `jadwal_notifikasi` → siapa saja yang jadwalnya cocok hari ini
3. Kirim notifikasi lewat salah satu/kombinasi channel berikut:
   - **In-app**: insert ke `log_notifikasi`, muncul sebagai badge saat siswa buka aplikasi
   - **WhatsApp Gateway** (Fonnte/Wablas): kirim pesan otomatis ke nomor WA siswa — paling efektif karena remaja lebih sering buka WA daripada buka aplikasi
   - **Email** (PHPMailer + SMTP gratis): sebagai cadangan/untuk laporan ke guru pembina

**Contoh setup Cron Job di cPanel:**
```
0 7,16 * * * php /home/namauser/public_html/cron/kirim_notifikasi.php
```

4. Siswa menekan tombol **"Sudah Minum"** di notifikasi/aplikasi → otomatis tercatat di tabel `konsumsi_ttd` (bagian 4) → langsung update ke dashboard UKS secara real-time

**Kenapa ini penting untuk konsistensi data:** log notifikasi + konfirmasi "sudah minum" inilah yang jadi salah satu variabel input untuk model regresi logistik (kepatuhan konsumsi TTD), jadi 3 fitur baru ini (saran, konsultasi, notifikasi) saling terhubung, bukan fitur terpisah-pisah.

---

### Ringkasan super singkat
**PHP + MySQL + Bootstrap + Chart.js**, dengan model regresi logistik dilatih di Python secara offline lalu "dipindahkan" jadi rumus sigmoid di PHP. Fitur saran otomatis dibuat rule-based dari tabel database, konsultasi ahli pakai sistem pesan asinkron biasa (bukan live chat), dan notifikasi TTD dijalankan lewat **Cron Job cPanel** (fitur gratis bawaan hosting) + WhatsApp Gateway. Semua ini dipilih spesifik karena **wajib bisa jalan di shared hosting biasa** — tanpa VPS, tanpa Docker, tanpa server Python/Node/WebSocket terpisah — sekaligus tetap sesuai anggaran penelitian OPSI.
