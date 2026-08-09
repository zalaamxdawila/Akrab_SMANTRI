# ADR-0002: Store Versioned Questionnaire Answer Snapshots

## Status

Accepted

## Date

2026-08-09

## Context

AKRAB saat ini hanya menyimpan skor agregat, data menstruasi, pola makan, dan
hasil laboratorium. Jawaban per pertanyaan hilang setelah skor dihitung. Hasil
lengkap membutuhkan transparansi jawaban, tetapi data lama tidak boleh
direkonstruksi atau diasumsikan. Struktur pertanyaan juga dapat berkembang.

## Decision

Tambahkan snapshot JSON nullable dan versioned pada setiap baris `kuesioner`.
Snapshot hanya berisi pertanyaan yang terlihat oleh siswa, jawaban yang sudah
lolos validasi, label tampilan, dan versi definisi. Snapshot ditulis dalam
transaksi yang sama dengan skor dan hasil risiko. Baris lama tetap `NULL` dan UI
menampilkan bahwa rincian jawaban belum tersedia.

Akses snapshot mengikuti boundary record kuesioner yang sudah ada: siswa untuk
dirinya sendiri, orang tua untuk relasi approved, serta UKS/superadmin melalui
guard dan audit yang berlaku. Snapshot tidak boleh masuk log atau metadata audit.

## Alternatives considered

- Rekonstruksi jawaban dari skor total: ditolak karena banyak kombinasi jawaban
  dapat menghasilkan skor yang sama dan hasilnya akan menyesatkan.
- Tabel satu baris per jawaban: ditunda karena menambah banyak query dan schema
  untuk kebutuhan tampilan snapshot yang tidak memerlukan analitik per item.
- Tidak menyimpan jawaban dan hanya memperindah skor: ditolak karena tidak
  memenuhi kebutuhan hasil lengkap yang telah dikonfirmasi.

## Consequences

- Pengisian baru dapat menjelaskan skor secara transparan.
- Data lama tetap valid tetapi tidak memiliki rincian per pertanyaan.
- Snapshot harus dibatasi ukuran, divalidasi sebelum encode, dan di-decode secara
  fail-safe.
- Perubahan teks pertanyaan masa depan tidak mengubah tampilan hasil historis
  karena label tersimpan bersama versi snapshot.
- Migrasi bersifat additive dan harus direhearse serta dibackup sebelum produksi.
