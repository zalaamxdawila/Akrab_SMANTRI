-- Sprint 18 deployment evidence. Run on a production clone or a read-only
-- production session. This script contains no credentials and makes no changes.
SET @student_id = 1;
SET @uks_id = 1;

EXPLAIN SELECT k.id, k.status, k.tanggal_kirim, b.tanggal_balas
FROM konsultasi k
LEFT JOIN balasan_konsultasi b ON b.konsultasi_id = k.id
WHERE k.siswa_id = @student_id
ORDER BY k.tanggal_kirim DESC, k.id DESC
LIMIT 10 OFFSET 0;

EXPLAIN SELECT k.id, k.status, k.tanggal_kirim, u.nama, b.tanggal_balas
FROM konsultasi k
JOIN users u ON u.id = k.siswa_id
LEFT JOIN balasan_konsultasi b ON b.konsultasi_id = k.id
ORDER BY k.status ASC, k.tanggal_kirim DESC
LIMIT 20 OFFSET 0;

EXPLAIN SELECT u.id, u.nama, u.kelas, u.username
FROM users u
WHERE u.role = 'siswa'
ORDER BY u.kelas ASC, u.nama ASC
LIMIT 25 OFFSET 0;

EXPLAIN SELECT id, judul, tanggal_publikasi
FROM artikel_edukasi
WHERE uks_id = @uks_id
ORDER BY tanggal_publikasi DESC, id DESC
LIMIT 15 OFFSET 0;

EXPLAIN SELECT psl.id, psl.requested_at, p.username
FROM parent_student_links psl
JOIN users p ON p.id = psl.parent_id AND p.role = 'orangtua'
WHERE psl.status = 'pending'
ORDER BY psl.requested_at ASC, psl.id ASC
LIMIT 25 OFFSET 0;

-- Sprint 27 superadmin read-only list evidence.
EXPLAIN SELECT id, nama, role, status, username, kelas, created_at
FROM users
WHERE role = 'siswa' AND status = 'active'
ORDER BY created_at DESC, id DESC
LIMIT 25 OFFSET 0;

EXPLAIN SELECT
    a.id,
    COALESCE(a.authenticated_actor_id, a.actor_id),
    COALESCE(a.effective_actor_id, a.actor_id),
    a.action,
    a.request_id,
    a.created_at
FROM audit_log a
WHERE COALESCE(a.authenticated_actor_id, a.actor_id) = 1
ORDER BY a.created_at DESC, a.id DESC
LIMIT 25 OFFSET 0;

-- Sprint 29 superadmin health master evidence.
EXPLAIN SELECT r.id, r.user_id, u.nama, r.tanggal_periksa, r.nilai_hb
FROM kadar_hb r
JOIN users u ON u.id = r.user_id AND u.role = 'siswa'
WHERE r.archived_at IS NULL AND (u.nama LIKE '%uat%' OR u.username LIKE '%uat%')
ORDER BY r.tanggal_periksa DESC, r.id DESC
LIMIT 25 OFFSET 0;

EXPLAIN SELECT kategori_risiko, COUNT(DISTINCT user_id)
FROM hasil_deteksi h
WHERE h.archived_at IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM hasil_deteksi newer
      WHERE newer.user_id = h.user_id AND newer.archived_at IS NULL
        AND (newer.tanggal > h.tanggal
          OR (newer.tanggal = h.tanggal AND newer.id > h.id))
  )
GROUP BY kategori_risiko;

-- Sprint 30 superadmin operational list evidence.
EXPLAIN SELECT r.id, r.tanggal_kirim, u.nama, r.status
FROM konsultasi r
JOIN users u ON u.id = r.siswa_id
WHERE r.archived_at IS NULL AND (u.nama LIKE '%uat%' OR r.pertanyaan LIKE '%uat%')
ORDER BY r.tanggal_kirim DESC, r.id DESC
LIMIT 25 OFFSET 0;

EXPLAIN SELECT r.id, r.tanggal_publikasi, r.judul
FROM artikel_edukasi r
WHERE r.archived_at IS NULL
ORDER BY r.tanggal_publikasi DESC, r.id DESC
LIMIT 25 OFFSET 0;
