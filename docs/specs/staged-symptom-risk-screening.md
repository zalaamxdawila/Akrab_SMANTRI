# Draft Spec: Skrining Bertahap Gejala dan Faktor Risiko

Status: IMPLEMENTED CANDIDATE — alur, threshold, dan rujukan mengikuti arahan
yang disampaikan; formula awal dibuat transparan dan tetap harus divalidasi ahli
medis sekolah terkait sebelum diperlakukan sebagai aturan klinis final.

## Objective

Mengubah pengisian siswa menjadi skrining bertahap tanpa ketergantungan pada Hb:

1. Sesudah login, siswa melengkapi data diri minimum: kelas, usia/tanggal lahir,
   dan gender.
2. Siswa hanya mengisi pertanyaan gejala pada tahap pertama.
3. Sistem menghitung skor gejala di server. Faktor risiko hanya dapat diakses
   bila ambang gejala terpenuhi.
4. Siswa yang tidak lolos ambang berhenti pada hasil gejala dan memperoleh
   penjelasan serta saran yang aman.
5. Siswa yang lolos mengisi faktor risiko, lalu memperoleh tingkat risiko dan
   rekomendasi tindak lanjut.

Keunggulan produk adalah **skrining awal sederhana berbasis gejala dan faktor
risiko tanpa mewajibkan pemeriksaan Hb pada aplikasi**. Hasil harus disebut
**skrining risiko/indikasi**, bukan diagnosis anemia. Diagnosis tetap memerlukan
penilaian tenaga kesehatan dan pemeriksaan yang sesuai.

AKRAB tidak menyatakan atau menyiratkan kerja sama, afiliasi, validasi, maupun
dukungan dari WHO, Kementerian Kesehatan, atau organisasi eksternal lain. Sumber
kewenangan untuk instrumen, threshold, bobot, redaksi hasil, dan jalur rujukan
fitur ini adalah ahli medis sekolah terkait.

## Alur Target

```text
Login siswa
  -> biodata belum lengkap? lengkapi biodata
  -> tahap gejala
      -> rata-rata gejala <= 4,6: hasil gejala + penjelasan; selesai
      -> rata-rata gejala > 4,6: tahap faktor risiko
          -> skor faktor risiko < 75%: terindikasi risiko anemia + rujukan
          -> skor faktor risiko >= 75%: hasil faktor risiko + saran pemantauan
```

Semua gate harus dihitung dan ditegakkan di server; penyembunyian tombol di
browser hanya untuk UX dan bukan kontrol akses.

## Sumber Instrumen dan Pemetaan Pertanyaan

Sumber pertanyaan kanonis untuk flow baru adalah `Kuesioner.pdf` di root
workspace. Teks pertanyaan dipertahankan sesuai sumber; koreksi ejaan hanya
boleh dilakukan tanpa mengubah makna dan harus tercatat dalam versi instrumen.

### Tahap 0 — Biodata setelah login

Data minimum sesuai kebutuhan produk dan bagian karakteristik/informed consent:

- Kelas/pendidikan.
- Tanggal lahir; usia dihitung saat skrining.
- Gender/jenis kelamin.

Nomor responden dibuat sistem. Inisial dapat diturunkan dari profil. Tempat
lahir dan alamat tidak ditampilkan pada flow utama kecuali ahli medis sekolah
terkait menetapkannya sebagai variabel yang diperlukan.

### Tahap 1 — Gejala anemia (Lampiran D, Bagian III)

Sepuluh pertanyaan skala keparahan:

1. Cepat lelah bila beraktivitas.
2. Pusing.
3. Mata berkunang-kunang.
4. Ujung tangan atau kaki sering dingin.
5. Suka sempoyongan.
6. Berdebar-debar walaupun beraktivitas ringan.
7. Mengantuk.
8. Malas beraktivitas.
9. Napas terasa pendek waktu beraktivitas.
10. Pucat.

PDF menyebut skala `0–10`, tetapi kepala tabel yang terekstrak menampilkan
`1–10`. Planning menggunakan `0–10` karena petunjuk tertulis dan implementasi
lama sama-sama memakai rentang tersebut; nilai minimum/maksimum tetap menjadi
golden boundary test.

Skor tahap gejala adalah rata-rata aritmetika sepuluh jawaban:

```text
skor_gejala = jumlah seluruh jawaban gejala / 10
```

- `skor_gejala > 4,6`: siswa dapat melanjutkan ke faktor risiko.
- `skor_gejala <= 4,6`: faktor risiko terkunci; siswa hanya melihat skor gejala,
  penjelasan, disclaimer, dan saran umum.

### Tahap 2 — Kandidat faktor risiko

Pemetaan sementara berdasarkan struktur instrumen:

**Faktor internal — Lampiran D, Bagian VI (menstruasi):**

1. Sudah atau belum mengalami menstruasi.
2. Usia mulai menstruasi (tahun dan bulan).
3. Keteraturan siklus setiap bulan.
4. Lama menstruasi setiap bulan (hari).
5. Jarak antarsiklus.

**Faktor eksternal — Lampiran D, Bagian VII (pola makan):**

- Tabel makanan dan jumlah untuk pagi, pukul 10, siang, pukul 16, dan malam.
- Sarapan setiap hari.
- Rutin makan siang.
- Selalu makan malam.
- Camilan antara pagi dan siang.
- Camilan antara siang dan malam.
- Makan/camilan menjelang tidur.

Pilihan enam kebiasaan makan adalah `Selalu`, `Kadang-kadang`, dan
`Tidak pernah`. Apakah tabel makanan ikut dihitung atau hanya menjadi data
pendukung harus diputuskan pada SR0.

Implementasi awal menormalisasi dua dimensi protektif ke `0–100%`:

- Faktor internal: menstruasi teratur `100`, tidak teratur `0`; belum
  menstruasi atau menstruasi tidak berlaku diberi nilai netral/protektif `100`.
- Faktor eksternal: setiap kebiasaan makan `Selalu = 100`,
  `Kadang-kadang = 50`, dan `Tidak pernah = 0`, lalu dirata-ratakan.
- Nilai akhir adalah rata-rata sederhana faktor internal dan faktor eksternal.
- Tabel rincian makanan disimpan sebagai konteks tetapi tidak diberi bobot.
- Nilai dibulatkan satu angka desimal sebelum disimpan; pembanding memakai nilai
  tersebut secara konsisten.

Nilai akhir faktor risiko:

- `< 75%`: keluaran **terindikasi/berisiko anemia berdasarkan gejala dan faktor
  risiko**, disertai rekomendasi UKS/Puskesmas/dokter sesuai decision table.
- `>= 75%`: tidak diberi label diagnosis anemia; tetap tampilkan penjelasan,
  pemantauan gejala, dan anjuran berkonsultasi bila keluhan menetap/memburuk.

Bobot tersebut tidak tercantum dalam PDF. Karena itu versi
`akrab-school-screening-v1` harus dipandang sebagai rule set awal yang dapat
diaudit, bukan formula tervalidasi eksternal; perubahan bobot di masa depan wajib
memakai versi baru dan persetujuan ahli medis sekolah terkait.

### Bagian PDF yang tidak masuk flow skrining baru

- Bagian II Hb: tidak ditanyakan dan tidak menjadi syarat hasil baru.
- Bagian IV sikap dan Bagian V pengetahuan: tidak muncul pada flow gejala/faktor
  risiko yang diminta. Data historisnya tetap dapat dibaca.
- Lampiran A penyaringan responden, Lampiran B informasi pasien, dan Lampiran C
  informed consent: bukan komponen skor. Kebutuhan consent digital merupakan
  keputusan tata kelola terpisah.

## Keputusan Teknis Awal

- Pertahankan data kuesioner lama dan hasil berbasis lab sebagai data historis.
- Buat versi instrumen/algoritma baru; jangan menafsirkan ulang hasil lama.
- Pisahkan `symptom_score`, status kelayakan faktor risiko, `risk_factor_score`,
  kategori, versi aturan, dan waktu penyelesaian per tahap.
- Snapshot hanya menyimpan pertanyaan yang benar-benar ditampilkan dan dijawab.
- Jangan meminta atau mewajibkan Hb/MCH/MCHC/MCV pada flow baru.
- Alur baru tidak menjalankan model Hb/clinical feature flag lama; rule set baru
  memiliki versi terpisah dan disclaimer non-diagnosis.
- Keempat perubahan merupakan satu state machine dan satu episode skrining,
  bukan formulir atau hasil yang berdiri sendiri.

## Data dan Privasi

- Data diri minimum berasal dari profil siswa agar tidak berulang pada setiap
  skrining; perubahan profil diaudit.
- Usia sebaiknya dihitung dari tanggal lahir pada tanggal skrining, bukan disimpan
  sebagai angka yang cepat usang. Keputusan ini menunggu persetujuan produk.
- Gender/jenis kelamin memakai enum dan terminologi yang disetujui ahli medis
  sekolah terkait.
- Data kesehatan tidak masuk log, URL, atau pesan error.

## Success Criteria

- Siswa baru tidak dapat membuka tahap gejala sebelum biodata wajib lengkap.
- Pada tahap awal, UI dan payload hanya memuat pertanyaan gejala.
- Payload yang mencoba melewati gate faktor risiko ditolak server.
- Skor gejala adalah rata-rata 10 jawaban; `4,5`, `4,6`, dan `4,7` masing-masing
  menghasilkan gate yang benar.
- Hasil tahap gejala tetap tersimpan dan dapat dibaca walau faktor risiko terkunci.
- Hasil faktor risiko memiliki kategori, alasan ringkas, saran UKS/Puskesmas/
  dokter, red-flag escalation, dan disclaimer non-diagnosis.
- Faktor risiko `74,9%` masuk hasil terindikasi risiko anemia; `75,0%` tidak,
  sesuai aturan produk dan setelah formula disetujui ahli medis sekolah terkait.
- UKS/superadmin dapat membedakan hasil `gejala_selesai`,
  `faktor_risiko_tersedia`, dan `selesai`; data lama tetap tampil.
- Seluruh unit, integration, authorization, migration, dan browser test lulus.

## Tech Stack

- PHP native, target dependency `^8.2`; runtime lokal saat planning PHP 8.5.8.
- PDO dan MariaDB/MySQL pada shared hosting.
- PHPUnit `^12.0` untuk unit dan integration tests.
- Session-based authentication, server-rendered HTML, dan JavaScript progresif
  hanya untuk UX.

## Commands

- Quality gate: `composer quality`
- PHPUnit fokus: `php vendor/bin/phpunit tests/Unit tests/Integration`
- Lint: `php tools/lint.php`
- Migration status/rehearsal: `php tools/migrate.php status`
- Release preflight: `php tools/preflight.php`

## Project Structure

- `siswa/`: route dan UI flow siswa.
- `app/Services/`: aturan gate, scoring, persistence, dan presentasi hasil.
- `app/Repositories/`: query hasil lintas role.
- `config/`: validasi dan clinical approval gate.
- `database/migrations/`: perubahan schema additive/versioned.
- `tests/Unit`, `tests/Integration`, `tests/browser`: bukti perilaku.

## Code Style

- File domain/service baru memakai `declare(strict_types=1)`.
- Aturan klinis berada dalam service/calculate class murni dan tidak ditulis di
  template atau JavaScript.
- Gunakan enum/status allowlist, prepared statements PDO, output encoding, dan
  transaksi untuk perubahan beberapa tabel.
- Nama status dan field menggunakan istilah netral, misalnya
  `terindikasi_risiko`, bukan `diagnosis_anemia`.

## Testing Strategy

- Unit: formula, boundary `4,6`/`75%`, state transition, presenter, dan red flags.
- Integration: persistence atomik, migration, ownership, CSRF, forged payload,
  cooldown, dan kompatibilitas data lama.
- Browser: login → biodata → gejala untuk dua cabang → faktor risiko → hasil,
  termasuk keyboard, mobile layout, back/refresh, dan direct URL bypass.
- Clinical golden cases: fixture input-output yang disetujui ahli medis sekolah
  terkait menjadi regression suite dan tidak boleh diperbarui hanya agar test
  lulus.

## Boundaries

### Always

- Hitung skor dan gate di server.
- Simpan versi instrumen/aturan pada setiap hasil.
- Tampilkan disclaimer bahwa hasil adalah skrining, bukan diagnosis.
- Pertahankan ownership/role checks dan data historis.

### Ask first

- Perubahan bobot, denominator, pembulatan, dan kategori dari versi awal.
- Validasi pemetaan menstruasi sebagai faktor internal dan pola makan sebagai
  faktor eksternal.
- Redaksi klaim dan rekomendasi klinis.
- Perubahan schema produksi dan aktivasi clinical feature flag.

### Never

- Menyatakan siswa “pasti/terdiagnosis anemia” hanya dari hasil aplikasi.
- Mengaktifkan aturan klinis yang belum disetujui ahli medis sekolah terkait.
- Menghapus data lama atau mengubah makna skor historis.
- Mengandalkan JavaScript untuk mencegah bypass tahap.

## Open Questions — Validasi Lanjutan Ahli

1. Apakah formula awal dua dimensi berbobot sama dan pembulatan satu desimal
   disetujui atau perlu versi baru?
2. Apakah pemetaan final faktor risiko adalah Bagian VI menstruasi sebagai
   faktor internal dan Bagian VII pola makan sebagai faktor eksternal?
3. Apakah rekomendasi UKS/Puskesmas/dokter ditentukan oleh kategori, red flag,
   atau kombinasi keduanya?
4. Apa red flag yang harus langsung diarahkan ke tenaga kesehatan tanpa menunggu
   skor total?
5. Apakah `gender` atau `jenis kelamin biologis` yang dibutuhkan oleh instrumen,
   dan apa pilihan nilainya?
6. Apakah pengisian tetap dibatasi cooldown enam bulan atau aturan baru berbeda?
7. Apakah tabel rincian makanan ikut diberi skor atau hanya disimpan sebagai
    data pendukung?

## Tabel Keputusan Produk

| Tahap | Kondisi | Status | Akses berikutnya | Keluaran minimum |
|---|---|---|---|---|
| Gejala | `<= 4,6` | `gejala_selesai` | Faktor risiko terkunci | Skor dan penjelasan gejala |
| Gejala | `> 4,6` | `faktor_risiko_tersedia` | Faktor risiko terbuka | Skor dan alasan lanjut |
| Faktor risiko | `< 75%` | `terindikasi_anemia` | Selesai | Indikasi risiko + rekomendasi |
| Faktor risiko | `>= 75%` | `tidak_terindikasi_anemia` | Selesai | Penjelasan + pemantauan |

Semua perbandingan dilakukan pada nilai presisi sebelum format tampilan. Aturan
pembulatan persentase faktor risiko memakai satu angka desimal pada versi awal.
