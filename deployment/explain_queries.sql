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
