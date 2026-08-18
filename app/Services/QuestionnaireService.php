<?php

declare(strict_types=1);

final class QuestionnaireService
{
    public function __construct(
        private PDO $pdo,
        private AnemiaRiskService $riskService = new AnemiaRiskService(),
        private QuestionnaireAnswerSnapshot $answerSnapshot = new QuestionnaireAnswerSnapshot(),
        private QuestionnaireEligibility $eligibility = new QuestionnaireEligibility()
    ) {
    }

    /** @return array{probability: float, category: string, model_version: string, model_checksum: string} */
    public function submit(int $userId, array $input): array
    {
        [$values, $answersSnapshot] = $this->validatedSubmission($input);
        $risk = $this->riskService->evaluate([
            'kadar_hb' => $values['kadar_hb'],
            'kadar_mchc' => $values['kadar_mchc'],
            'kadar_mcv' => $values['kadar_mcv'],
            'kadar_mch' => $values['kadar_mch'],
            'skor_gejala' => $values['skor_gejala'],
            'skor_makan' => $values['skor_makan'],
            'mens_teratur' => $values['mens_teratur'],
        ]);

        $this->persist($userId, $values, $answersSnapshot, $risk);

        return $risk;
    }

    public function collect(int $userId, array $input): void
    {
        [$values, $answersSnapshot] = $this->validatedSubmission($input);
        $this->persist($userId, $values, $answersSnapshot, null);
    }

    /** @return array{0: array<string, mixed>, 1: string} */
    private function validatedSubmission(array $input): array
    {
        $validated = validateQuestionnaireInput($input);
        if (!$validated['valid']) {
            throw new InvalidArgumentException(implode(' ', $validated['errors']));
        }

        return [$validated['values'], $this->answerSnapshot->encode($input)];
    }

    /**
     * @param array<string, mixed> $values
     * @param array{probability: float, category: string, model_version: string, model_checksum: string}|null $risk
     */
    private function persist(int $userId, array $values, string $answersSnapshot, ?array $risk): void
    {

        $this->pdo->beginTransaction();
        try {
            $this->lockStudentAndAssertEligibility($userId);
            $respondentNumber = 'AKRAB-' . date('Ym') . '-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(bin2hex(random_bytes(3)));
            $education = $values['pendidikan'] . (!empty($values['jurusan']) ? ' ' . $values['jurusan'] : '');
            $stmt = $this->pdo->prepare('INSERT INTO kuesioner
                (user_id, tanggal_wawancara, nomor_responden, inisial_responden, tanggal_lahir, tempat_lahir, alamat, pendidikan,
                 kadar_hb, kadar_mchc, kadar_mcv, kadar_mch, skor_gejala, skor_sikap, skor_pengetahuan,
                 mens_sudah, mens_usia_th, mens_teratur, mens_lama_hari, mens_jarak_siklus, skor_makan, makanan_dikonsumsi,
                 skor_faktor_internal, skor_faktor_eksternal, answers_snapshot)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $userId, $values['tanggal_wawancara'], $respondentNumber, $values['inisial_responden'], $values['tanggal_lahir'],
                $values['tempat_lahir'], $values['alamat'], $education, $values['kadar_hb'], $values['kadar_mchc'],
                $values['kadar_mcv'], $values['kadar_mch'], $values['skor_gejala'], $values['skor_sikap'],
                $values['skor_pengetahuan'], $values['mens_sudah'], $values['mens_usia_th'], $values['mens_teratur'],
                $values['mens_lama_hari'], $values['mens_jarak_siklus'], $values['skor_makan'], $values['makanan_dikonsumsi'],
                $values['skor_faktor_internal'], $values['skor_faktor_eksternal'],
                $answersSnapshot,
            ]);
            $questionnaireId = (int) $this->pdo->lastInsertId();

            if ($risk !== null) {
                $result = $this->pdo->prepare('INSERT INTO hasil_deteksi
                    (user_id, questionnaire_id, probabilitas_risiko, kategori_risiko,
                     model_version, model_checksum, tanggal)
                    VALUES (?, ?, ?, ?, ?, ?, CURDATE())');
                $result->execute([
                    $userId,
                    $questionnaireId,
                    $risk['probability'],
                    $risk['category'],
                    $risk['model_version'],
                    $risk['model_checksum'],
                ]);
            }
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }

    }

    private function lockStudentAndAssertEligibility(int $userId): void
    {
        $forUpdate = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'
            ? ' FOR UPDATE'
            : '';
        $student = $this->pdo->prepare(
            'SELECT id FROM users WHERE id = ?' . $forUpdate
        );
        $student->execute([$userId]);
        if (!$student->fetch()) {
            throw new RuntimeException('Questionnaire user does not exist.');
        }

        $latest = $this->pdo->prepare(
            'SELECT created_at
             FROM kuesioner
             WHERE user_id = ? AND archived_at IS NULL
             ORDER BY created_at DESC, id DESC
             LIMIT 1'
        );
        $latest->execute([$userId]);
        $latestCreatedAt = $latest->fetchColumn();
        $status = $this->eligibility->forLatestSubmission(
            is_string($latestCreatedAt) ? $latestCreatedAt : null
        );
        if (!$status['allowed']) {
            $date = $status['next_eligible_at']?->format('d M Y') ?? '-';
            throw new InvalidArgumentException(
                'Kuesioner dapat diisi kembali pada ' . $date . '.'
            );
        }
    }
}
