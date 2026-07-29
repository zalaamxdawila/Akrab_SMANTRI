# Data Retention, Correction, and Deletion

## Classification

- Restricted: hasil skrining, kuesioner, menstruasi, konsumsi TTD, konsultasi, dan relasi wali.
- Confidential: identitas akun, audit actor/target, dan metadata operasional.
- Internal: configuration template, runbook, dan metrik agregat tanpa identitas.

## Baseline retention

- Audit event: 365 hari, dipangkas oleh `cron/purge_audit_log.php`.
- Application JSON log: 30 hari.
- Backup: mengikuti jadwal backup; expired backup harus dihapus secara aman.
- Data kesehatan/akun: dipertahankan selama tujuan program dan kewajiban institusi berlaku. Durasi final harus disetujui data owner sebelum produksi.

## Correction/deletion request

1. Verifikasi identitas pemohon dan kewenangannya tanpa meminta secret akun.
2. Catat request ID, scope data, dasar keputusan, approver, dan deadline.
3. Temukan seluruh record terkait termasuk relasi, audit yang wajib dipertahankan, dan backup.
4. Koreksi/hapus melalui transaksi yang ditinjau; jangan menjalankan SQL ad-hoc tanpa backup.
5. Audit tindakan tanpa menyalin nilai kesehatan lama/baru.
6. Beri tahu pemohon dan catat kapan backup kedaluwarsa akan menghilangkan salinan historis.

Legal hold atau investigasi insiden menangguhkan penghapusan hanya dengan keputusan tertulis data owner/security owner.
