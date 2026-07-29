# UAT Login As Superadmin

Date: 2026-07-29

## Matrix

| Target | Read navigation | Allowed mutation | Blocked mutation |
|---|---|---|---|
| Siswa | dashboard dan halaman siswa | TTD, menstruasi, kuesioner, konsultasi | credential, export, master action |
| UKS | dashboard dan halaman UKS | reply konsultasi, artikel milik sendiri | import/export, credential, parent-link review |
| Orang tua | dashboard | request relasi anak | credential/master action |

Semua journey wajib menampilkan banner actor efektif, countdown, tombol kembali
POST+CSRF, dan audit dual-actor. Password step-up tidak boleh masuk log, audit,
checkpoint, screenshot, atau source.
