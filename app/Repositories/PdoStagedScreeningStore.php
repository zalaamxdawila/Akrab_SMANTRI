<?php

declare(strict_types=1);

final class PdoStagedScreeningStore implements StagedScreeningStore
{
    public function __construct(
        private PDO $pdo,
        private QuestionnaireEligibility $eligibility = new QuestionnaireEligibility()
    ) {
    }

    public static function schemaIsReady(PDO $pdo): bool
    {
        try {
            $statement = $pdo->prepare(
                'SELECT COUNT(*) FROM schema_migrations WHERE version IN (?, ?)'
            );
            $statement->execute([
                '021_staged_screening',
                '022_questionnaire_retake_history',
            ]);
            return (int) $statement->fetchColumn() === 2;
        } catch (Throwable) {
            return false;
        }
    }

    public function createSymptomScreening(
        int $userId,
        array $values,
        array $score,
        string $snapshot
    ): int {
        $this->pdo->beginTransaction();
        try {
            $lock = $this->lockSuffix();
            $student = $this->pdo->prepare(
                "SELECT id, nama, username FROM users
                 WHERE id = ? AND role = 'siswa' AND status = 'active'" . $lock
            );
            $student->execute([$userId]);
            $studentRow = $student->fetch();
            if (!$studentRow) {
                throw new RuntimeException('Akun siswa aktif tidak ditemukan.');
            }

            $latest = $this->pdo->prepare(
                'SELECT created_at FROM kuesioner
                 WHERE user_id = ? AND archived_at IS NULL
                   AND history_only_at IS NULL
                 ORDER BY created_at DESC, id DESC LIMIT 1'
            );
            $latest->execute([$userId]);
            $latestCreatedAt = $latest->fetchColumn();
            $eligibility = $this->eligibility->forLatestSubmission(
                is_string($latestCreatedAt) ? $latestCreatedAt : null
            );
            if (!$eligibility['allowed']) {
                $date = $eligibility['next_eligible_at']?->format('d M Y') ?? '-';
                throw new InvalidArgumentException('Kuesioner dapat diisi kembali pada ' . $date . '.');
            }

            $stage = $score['risk_eligible']
                ? 'faktor_risiko_tersedia'
                : 'gejala_selesai';
            $outcome = $score['risk_eligible'] ? null : 'gejala_di_bawah_ambang';
            $respondentNumber = 'AKRAB-' . date('Ym') . '-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT)
                . '-' . strtoupper(bin2hex(random_bytes(3)));
            $initials = $this->initials((string) $studentRow['nama']);

            $statement = $this->pdo->prepare(
                'INSERT INTO kuesioner (
                    user_id, tanggal_wawancara, nomor_responden, inisial_responden,
                    tanggal_lahir, jenis_kelamin, pendidikan, skor_gejala,
                    answers_snapshot, tahap_screening, rerata_gejala,
                    hasil_screening, versi_screening
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $statement->execute([
                $userId,
                date('Y-m-d'),
                $respondentNumber,
                $initials,
                $values['tanggal_lahir'],
                $values['jenis_kelamin'],
                $values['pendidikan'],
                $score['total'],
                $snapshot,
                $stage,
                $score['average'],
                $outcome,
                StagedScreeningScore::VERSION,
            ]);
            $questionnaireId = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();
            return $questionnaireId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function findRiskEligible(int $userId, int $questionnaireId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, user_id, jenis_kelamin, tahap_screening, rerata_gejala, answers_snapshot
             FROM kuesioner
             WHERE id = ? AND user_id = ? AND archived_at IS NULL
               AND history_only_at IS NULL
             LIMIT 1'
        );
        $statement->execute([$questionnaireId, $userId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findLatestRiskEligible(int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT id, user_id, jenis_kelamin, tahap_screening, rerata_gejala, answers_snapshot
             FROM kuesioner
             WHERE user_id = ? AND tahap_screening = 'faktor_risiko_tersedia'
               AND rerata_gejala > ? AND archived_at IS NULL
               AND history_only_at IS NULL
             ORDER BY created_at DESC, id DESC
             LIMIT 1"
        );
        $statement->execute([$userId, StagedScreeningScore::SYMPTOM_THRESHOLD]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function completeRiskFactorScreening(
        int $userId,
        int $questionnaireId,
        array $values,
        array $score,
        string $snapshot
    ): void {
        $this->pdo->beginTransaction();
        try {
            $current = $this->pdo->prepare(
                'SELECT id, tahap_screening, rerata_gejala
                 FROM kuesioner
                 WHERE id = ? AND user_id = ? AND archived_at IS NULL
                   AND history_only_at IS NULL'
                . $this->lockSuffix()
            );
            $current->execute([$questionnaireId, $userId]);
            $row = $current->fetch();
            if (
                !$row
                || $row['tahap_screening'] !== 'faktor_risiko_tersedia'
                || (float) $row['rerata_gejala'] <= StagedScreeningScore::SYMPTOM_THRESHOLD
            ) {
                throw new InvalidArgumentException('Tahap faktor risiko tidak tersedia untuk hasil gejala ini.');
            }

            $outcome = $score['anemia_indicated']
                ? 'terindikasi_anemia'
                : 'tidak_terindikasi_anemia';
            $update = $this->pdo->prepare(
                "UPDATE kuesioner SET
                    mens_sudah = ?, mens_usia_th = ?, mens_teratur = ?,
                    mens_lama_hari = ?, mens_jarak_siklus = ?,
                    skor_makan = ?, makanan_dikonsumsi = ?,
                    skor_faktor_internal = ?, skor_faktor_eksternal = ?,
                    answers_snapshot = ?, tahap_screening = 'selesai',
                    persentase_faktor_risiko = ?, hasil_screening = ?
                 WHERE id = ? AND user_id = ?
                   AND tahap_screening = 'faktor_risiko_tersedia'
                   AND rerata_gejala > ? AND archived_at IS NULL
                   AND history_only_at IS NULL"
            );
            $update->execute([
                $values['mens_sudah'],
                $values['mens_usia_th'],
                $values['mens_teratur'],
                $values['mens_lama_hari'],
                $values['mens_jarak_siklus'],
                $values['skor_makan'],
                $values['makanan_dikonsumsi'],
                (int) round($score['internal_percentage']),
                (int) round($score['external_percentage']),
                $snapshot,
                $score['percentage'],
                $outcome,
                $questionnaireId,
                $userId,
                StagedScreeningScore::SYMPTOM_THRESHOLD,
            ]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('Penyimpanan faktor risiko gagal karena status berubah.');
            }
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function findForStudent(int $userId, int $questionnaireId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT k.*, u.nama, u.username
             FROM kuesioner k
             INNER JOIN users u ON u.id = k.user_id
             WHERE k.id = ? AND k.user_id = ?
               AND k.archived_at IS NULL AND k.versi_screening IS NOT NULL
             LIMIT 1'
        );
        $statement->execute([$questionnaireId, $userId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findLatestForStudent(int $userId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT k.*, u.nama, u.username
             FROM kuesioner k
             INNER JOIN users u ON u.id = k.user_id
             WHERE k.user_id = ? AND k.archived_at IS NULL
               AND k.history_only_at IS NULL
               AND k.versi_screening IS NOT NULL
             ORDER BY k.created_at DESC, k.id DESC
             LIMIT 1'
        );
        $statement->execute([$userId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    private function lockSuffix(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'
            ? ' FOR UPDATE'
            : '';
    }

    private function initials(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = '';
        foreach ($words as $word) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }
        return mb_substr($initials !== '' ? $initials : 'S', 0, 20);
    }
}
