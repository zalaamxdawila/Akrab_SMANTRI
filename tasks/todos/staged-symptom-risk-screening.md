# Todo: Skrining Bertahap Gejala dan Faktor Risiko

- [!] SR0 — Validasi final clinical decision table dan formula awal dengan ahli
  medis sekolah terkait; threshold/pemetaan sudah diterapkan.
- [x] SR1 — Tambahkan biodata pada tahap awal siswa.
- [x] SR2 — Tambahkan state/versioned data model secara additive.
- [x] Checkpoint A — Contract teknis, schema snapshot, dan compatibility unit hijau.
- [x] SR3 — Implementasikan calculator gejala dan golden tests.
- [x] SR4 — Implementasikan tahap submit gejala.
- [x] SR5 — Implementasikan hasil gejala dan server-side gate.
- [!] Checkpoint B — Dua cabang gejala lulus unit/contract; browser MCP tidak tersedia.
- [x] SR6 — Implementasikan calculator faktor risiko versi awal.
- [x] SR7 — Implementasikan tahap faktor risiko dan hasil akhir.
- [!] Checkpoint C — Boundary dan rujukan lulus unit; clinical review final tersisa.
- [x] SR8a — Adaptasikan hasil siswa.
- [x] SR8b — Adaptasikan detail UKS dan superadmin.
- [x] SR8c — Adaptasikan aggregate dan export versioned; format lama dan staged
  dipisahkan, sedangkan field yang tidak ditanyakan diekspor kosong.
- [!] SR9 — Quality gate dan release produksi selesai; authenticated human-browser
  UAT serta sign-off formula akhir ahli medis sekolah terkait masih diperlukan.

Sumber pertanyaan sudah ditetapkan: `Kuesioner.pdf`. Pemetaan planning:

- Bagian III: 10 pertanyaan gejala.
- Bagian VI: kandidat faktor internal (menstruasi).
- Bagian VII: kandidat faktor eksternal (pola makan).
- Bagian II Hb, IV sikap, dan V pengetahuan: tidak tampil dalam flow baru.

Keputusan produk sudah terkunci sebagai satu alur:

- Skor gejala = jumlah 10 jawaban skala 0–10 dibagi 10.
- `> 4,6` membuka faktor risiko; `<= 4,6` berhenti pada hasil gejala.
- Faktor risiko `< 75%` menghasilkan status terindikasi/berisiko anemia.
- Flow dan hasil baru tidak bergantung pada Hb.

Formula awal yang diterapkan: faktor internal dan eksternal berbobot sama;
`Selalu/Kadang-kadang/Tidak pernah = 100/50/0`; tabel makanan hanya konteks;
pembulatan satu desimal. Validasi final formula dan red flags oleh ahli medis
sekolah terkait masih tercatat sebagai tindak lanjut.

Status release 2026-08-30:

- migrasi additive `021_staged_screening` diterapkan dan diverifikasi idempotent;
- runner migrasi sekali-pakai telah diganti tombstone HTTP 404;
- deployment terbatas ke document root `akrab.portodq.com`;
- lint, dependency audit, focused tests, adversarial review, file-size parity,
  dan HTTP smoke lulus;
- route publik sehat, route privat tidak menghasilkan HTTP 500;
- Chrome DevTools MCP dan akun uji produksi tidak tersedia untuk UAT login.
