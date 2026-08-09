<?php

declare(strict_types=1);

final class QuestionnaireService
{
    public function __construct(
        private PDO $pdo,
        private AnemiaRiskService $riskService = new AnemiaRiskService(),
        private QuestionnaireAnswerSnapshot $answerSnapshot = new QuestionnaireAnswerSnapshot()
    ) {
    }

    /** @return array{probability: float, category: string, model_version: string, model_checksum: string} */
    public function submit(int $userId, array $input): array
    {
        $validated = validateQuestionnaireInput($input);
        if (!$validated['valid']) {
            throw new InvalidArgumentException(implode(' ', $validated['errors']));
        }

        $values = $validated['values'];
        $answersSnapshot = $this->answerSnapshot->encode($input);
        $risk = $this->riskService->evaluate([
            'kadar_hb' => $values['kadar_hb'],
            'kadar_mchc' => $values['kadar_mchc'],
            'kadar_mcv' => $values['kadar_mcv'],
            'kadar_mch' => $values['kadar_mch'],
            'skor_gejala' => $values['skor_gejala'],
            'skor_makan' => $values['skor_makan'],
            'mens_teratur' => $values['mens_teratur'],
        ]);

        $this->pdo->beginTransaction();
        try {
            $respondentNumber = 'AKRAB-' . date('Ym') . '-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(bin2hex(random_bytes(3)));
            $education = $values['pendidikan'] . (!empty($values['jurusan']) ? ' ' . $values['jurusan'] : '');
            $stmt = $this->pdo->prepare('INSERT INTO kuesioner
                (user_id, tanggal_wawancara, nomor_responden, inisial_responden, tanggal_lahir, tempat_lahir, alamat, pendidikan,
                 kadar_hb, kadar_mchc, kadar_mcv, kadar_mch, skor_gejala, skor_sikap, skor_pengetahuan,
                 mens_sudah, mens_usia_th, mens_teratur, mens_lama_hari, mens_jarak_siklus, skor_makan, makanan_dikonsumsi,
                 answers_snapshot)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $userId, $values['tanggal_wawancara'], $respondentNumber, $values['inisial_responden'], $values['tanggal_lahir'],
                $values['tempat_lahir'], $values['alamat'], $education, $values['kadar_hb'], $values['kadar_mchc'],
                $values['kadar_mcv'], $values['kadar_mch'], $values['skor_gejala'], $values['skor_sikap'],
                $values['skor_pengetahuan'], $values['mens_sudah'], $values['mens_usia_th'], $values['mens_teratur'],
                $values['mens_lama_hari'], $values['mens_jarak_siklus'], $values['skor_makan'], $values['makanan_dikonsumsi'],
                $answersSnapshot,
            ]);

            $result = $this->pdo->prepare('INSERT INTO hasil_deteksi
                (user_id, probabilitas_risiko, kategori_risiko, model_version, model_checksum, tanggal)
                VALUES (?, ?, ?, ?, ?, CURDATE())');
            $result->execute([$userId, $risk['probability'], $risk['category'], $risk['model_version'], $risk['model_checksum']]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }

        return $risk;
    }
}
