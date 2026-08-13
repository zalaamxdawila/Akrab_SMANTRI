# Simulasi Regresi Logistik Penelitian

## Objective

Mengaktifkan simulasi regresi logistik sebagai fitur utama proposal AKRAB. Siswa
dapat melengkapi Hb, MCHC, MCV, dan MCH pada kuesioner terakhir tanpa mengulang
pertanyaan. Hasil menjelaskan input, nilai linear `z`, sigmoid, probabilitas, dan
kategori secara transparan. Hasil selalu berlabel simulasi penelitian dan bukan
diagnosis medis.

## Contract

- Empat nilai laboratorium wajib diisi bersamaan untuk menjalankan model.
- Pengisian pertama dapat dilakukan siswa pada kuesioner miliknya yang belum
  mempunyai data laboratorium.
- Setelah tersimpan, siswa tidak dapat mengubah nilai langsung.
- Siswa dapat membuat satu permintaan perubahan dengan nilai pengganti lengkap.
- UKS atau superadmin dapat menyetujui atau menolak permintaan yang masih pending.
- Persetujuan memperbarui kuesioner dan menghitung hasil model baru dalam satu
  transaksi. Hasil lama dipertahankan sebagai histori, bukan ditimpa.
- Semua aksi mutasi memakai CSRF, pemeriksaan role/ownership, validasi rentang,
  transaksi, serta audit tanpa menyalin nilai kesehatan ke metadata audit.

## Model presentation

- Persamaan penelitian saat ini ditampilkan sebagai
  `z = 15.5 - 1.5(Hb) - 0.1(MCH-29.5) - 0.1(MCHC-33.2) - 0.05(MCV-90)`.
- MCH, MCHC, dan MCV dipusatkan terhadap nilai acuan model. Tanpa pemusatan,
  ketiga indeks memberi penalti konstan sekitar `-10.02` dan membuat hampir
  semua probabilitas dibulatkan menjadi `0.00%`.
- Sigmoid ditampilkan sebagai `P = 1 / (1 + e^-z)`.
- Batas kategori: rendah `<33%`, sedang `33–<66%`, tinggi `≥66%`.
- UI menampilkan contoh perhitungan dari data siswa, diagram alur, versi model,
  dan disclaimer penelitian.

## Validation

- Hb: 0–30 g/dL; MCHC: 0–100 g/dL; MCV: 0–200 fL; MCH: 0–100 pg.
- Nilai harus numerik finite; format atau nilai di luar rentang ditolak.
- Kuesioner harus aktif dan dimiliki siswa yang sedang login.

## Commands and testing

- Lint: `php tools/lint.php`
- Unit: `php -d extension=mbstring -d extension=pdo_sqlite vendor/bin/phpunit --testsuite Unit`
- Full: `php -d extension=mbstring -d extension=pdo_sqlite vendor/bin/phpunit`
- Unit test untuk matematika/presentasi model.
- Integration test untuk pengisian awal, duplicate submission, request edit,
  authorization, approval/rejection, transaksi, dan histori hasil.

## Boundaries

- Always: fail closed, parameterized SQL, escape output, preserve audit trail.
- Ask first: aktivasi produksi dan perubahan koefisien model.
- Never: klaim diagnosis/validasi klinis, log nilai lab, atau izinkan siswa
  mengubah data tersimpan tanpa approval.

## Success criteria

- Siswa dapat melengkapi empat nilai lab sekali dan langsung memperoleh hasil.
- Perubahan berikutnya hanya terjadi setelah approval UKS/superadmin.
- Halaman hasil menjelaskan proses regresi logistik secara detail dan responsif.
- Data tanpa empat nilai lengkap tidak disebut hasil regresi logistik.
- Seluruh tes, lint, production preflight, backup, dan smoke test lulus sebelum
  aktivasi produksi.

## Open questions

Tidak ada. Intent dikonfirmasi pengguna pada 2026-08-14.
