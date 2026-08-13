<?php

declare(strict_types=1);

final class QuestionnaireLabService
{
    public function __construct(
        private PDO $pdo,
        private AnemiaRiskService $riskService = new AnemiaRiskService()
    ) {
    }

    /** @return array<string, mixed> */
    public function completeInitial(int $studentId, array $input): array
    {
        $values = $this->validatedLab($input);
        $this->pdo->beginTransaction();
        try {
            $questionnaire = $this->lockLatestQuestionnaire($studentId);
            foreach (array_keys($values) as $field) {
                if ($questionnaire[$field] !== null && $questionnaire[$field] !== '') {
                    throw new InvalidArgumentException(
                        'Data laboratorium sudah tersimpan. Ajukan permintaan perubahan.'
                    );
                }
            }
            $risk = $this->riskService->explainLogistic($values);
            $this->updateQuestionnaire((int) $questionnaire['id'], $values);
            $this->insertDetection($studentId, (int) $questionnaire['id'], $risk);
            $this->pdo->commit();
            return $risk;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function requestChange(int $studentId, array $input): int
    {
        $values = $this->validatedLab($input);
        $this->pdo->beginTransaction();
        try {
            $questionnaire = $this->lockLatestQuestionnaire($studentId);
            foreach (array_keys($values) as $field) {
                if ($questionnaire[$field] === null || $questionnaire[$field] === '') {
                    throw new InvalidArgumentException('Lengkapi data laboratorium terlebih dahulu.');
                }
            }
            $pending = $this->pdo->prepare(
                "SELECT id FROM lab_change_requests
                 WHERE questionnaire_id = ? AND student_id = ? AND status = 'pending'
                 LIMIT 1"
            );
            $pending->execute([(int) $questionnaire['id'], $studentId]);
            if ($pending->fetchColumn()) {
                throw new InvalidArgumentException('Permintaan perubahan masih menunggu persetujuan.');
            }
            $statement = $this->pdo->prepare(
                'INSERT INTO lab_change_requests
                 (questionnaire_id, student_id, kadar_hb, kadar_mchc, kadar_mcv, kadar_mch)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $statement->execute([
                (int) $questionnaire['id'], $studentId, $values['kadar_hb'],
                $values['kadar_mchc'], $values['kadar_mcv'], $values['kadar_mch'],
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }

    /** @return array<string, mixed>|null */
    public function pendingForStudent(int $studentId): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT id, status, created_at FROM lab_change_requests
             WHERE student_id = ? AND status = 'pending'
             ORDER BY created_at DESC, id DESC LIMIT 1"
        );
        $statement->execute([$studentId]);
        return $statement->fetch() ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function pendingRequests(): array
    {
        return $this->pdo->query(
            "SELECT lcr.*, u.nama, u.username, u.kelas,
                    k.kadar_hb current_hb, k.kadar_mchc current_mchc,
                    k.kadar_mcv current_mcv, k.kadar_mch current_mch
             FROM lab_change_requests lcr
             JOIN users u ON u.id = lcr.student_id AND u.role = 'siswa'
             JOIN kuesioner k ON k.id = lcr.questionnaire_id
             WHERE lcr.status = 'pending'
             ORDER BY lcr.created_at, lcr.id LIMIT 200"
        )->fetchAll();
    }

    public function review(int $requestId, int $reviewerId, string $reviewerRole, bool $approve): void
    {
        if (!in_array($reviewerRole, ['uks', 'superadmin'], true)) {
            throw new RuntimeException('Reviewer tidak berwenang.');
        }
        $this->pdo->beginTransaction();
        try {
            $suffix = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
            $statement = $this->pdo->prepare(
                "SELECT * FROM lab_change_requests WHERE id = ? AND status = 'pending'" . $suffix
            );
            $statement->execute([$requestId]);
            $request = $statement->fetch();
            if (!$request) throw new InvalidArgumentException('Permintaan tidak ditemukan atau sudah diproses.');

            if ($approve) {
                $values = $this->validatedLab($request);
                $risk = $this->riskService->explainLogistic($values);
                $this->updateQuestionnaire((int) $request['questionnaire_id'], $values);
                $this->insertDetection((int) $request['student_id'], (int) $request['questionnaire_id'], $risk);
            }
            $update = $this->pdo->prepare(
                'UPDATE lab_change_requests SET status = ?, reviewed_by = ?, reviewer_role = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?'
            );
            $update->execute([$approve ? 'approved' : 'rejected', $reviewerId, $reviewerRole, $requestId]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }

    /** @return array{kadar_hb:float,kadar_mchc:float,kadar_mcv:float,kadar_mch:float} */
    private function validatedLab(array $input): array
    {
        $ranges = ['kadar_hb' => [0, 30], 'kadar_mchc' => [0, 100], 'kadar_mcv' => [0, 200], 'kadar_mch' => [0, 100]];
        $values = [];
        foreach ($ranges as $field => [$min, $max]) {
            $raw = $input[$field] ?? null;
            if (is_array($raw) || !is_numeric($raw)) throw new InvalidArgumentException('Semua data laboratorium wajib diisi dengan angka.');
            $value = (float) $raw;
            if (!is_finite($value) || $value < $min || $value > $max) throw new InvalidArgumentException('Nilai data laboratorium di luar rentang yang diizinkan.');
            $values[$field] = $value;
        }
        return $values;
    }

    /** @return array<string, mixed> */
    private function lockLatestQuestionnaire(int $studentId): array
    {
        $suffix = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
        $statement = $this->pdo->prepare(
            'SELECT id, kadar_hb, kadar_mchc, kadar_mcv, kadar_mch FROM kuesioner
             WHERE user_id = ? AND archived_at IS NULL ORDER BY created_at DESC, id DESC LIMIT 1' . $suffix
        );
        $statement->execute([$studentId]);
        $row = $statement->fetch();
        if (!$row) throw new InvalidArgumentException('Kuesioner aktif belum tersedia.');
        return $row;
    }

    private function updateQuestionnaire(int $questionnaireId, array $values): void
    {
        $statement = $this->pdo->prepare('UPDATE kuesioner SET kadar_hb = ?, kadar_mchc = ?, kadar_mcv = ?, kadar_mch = ? WHERE id = ? AND archived_at IS NULL');
        $statement->execute([$values['kadar_hb'], $values['kadar_mchc'], $values['kadar_mcv'], $values['kadar_mch'], $questionnaireId]);
        if ($statement->rowCount() !== 1) throw new RuntimeException('Kuesioner tidak dapat diperbarui.');
    }

    private function insertDetection(int $studentId, int $questionnaireId, array $risk): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO hasil_deteksi
             (user_id, questionnaire_id, probabilitas_risiko, kategori_risiko, model_version, model_checksum, tanggal)
             VALUES (?, ?, ?, ?, ?, ?, CURRENT_DATE)'
        );
        $statement->execute([
            $studentId, $questionnaireId, $risk['probability'], $risk['category'],
            $risk['model_version'],
            $risk['model_checksum'],
        ]);
    }
}
