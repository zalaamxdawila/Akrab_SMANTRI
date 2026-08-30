<?php

declare(strict_types=1);

final class StagedScreeningScore
{
    public const VERSION = 'akrab-school-screening-v1';
    public const SYMPTOM_THRESHOLD = 4.6;
    public const RISK_THRESHOLD = 75.0;

    /**
     * @param list<int|numeric-string> $answers
     * @return array{total: int, average: float, risk_eligible: bool}
     */
    public function symptoms(array $answers): array
    {
        if (count($answers) !== 10) {
            throw new InvalidArgumentException('Sepuluh jawaban gejala wajib diisi.');
        }

        $total = 0;
        foreach ($answers as $answer) {
            $value = filter_var($answer, FILTER_VALIDATE_INT);
            if ($value === false || $value < 0 || $value > 10) {
                throw new InvalidArgumentException('Nilai gejala harus berupa angka 0 sampai 10.');
            }
            $total += $value;
        }

        $average = round($total / 10, 1);

        return [
            'total' => $total,
            'average' => $average,
            'risk_eligible' => $average > self::SYMPTOM_THRESHOLD,
        ];
    }

    /**
     * Menstruation and eating habits are two equal-weight dimensions.
     * The source questionnaire does not provide clinical weights, so the
     * transformation remains intentionally simple and auditable.
     *
     * @param array<string, mixed> $answers
     * @return array{
     *     internal_percentage: float,
     *     external_percentage: float,
     *     percentage: float,
     *     anemia_indicated: bool
     * }
     */
    public function riskFactors(array $answers): array
    {
        $menstruation = (string) ($answers['mens_sudah'] ?? '');
        if (!in_array($menstruation, ['ya', 'belum'], true)) {
            throw new InvalidArgumentException('Status menstruasi tidak valid.');
        }

        if ($menstruation === 'belum') {
            $internal = 100.0;
        } else {
            $regularity = (string) ($answers['mens_teratur'] ?? '');
            if (!in_array($regularity, ['ya', 'tidak'], true)) {
                throw new InvalidArgumentException('Keteraturan menstruasi wajib diisi.');
            }
            $internal = $regularity === 'ya' ? 100.0 : 0.0;
        }

        $dietWeights = ['selalu' => 100.0, 'kadang' => 50.0, 'tidak' => 0.0];
        $dietTotal = 0.0;
        for ($index = 1; $index <= 6; $index++) {
            $habit = (string) ($answers['makan_' . $index] ?? '');
            if (!array_key_exists($habit, $dietWeights)) {
                throw new InvalidArgumentException('Kebiasaan makan wajib diisi dengan pilihan yang tersedia.');
            }
            $dietTotal += $dietWeights[$habit];
        }

        $external = round($dietTotal / 6, 1);
        $percentage = round(($internal + $external) / 2, 1);

        return [
            'internal_percentage' => $internal,
            'external_percentage' => $external,
            'percentage' => $percentage,
            'anemia_indicated' => $percentage < self::RISK_THRESHOLD,
        ];
    }
}
