<?php

declare(strict_types=1);

final class AnemiaRiskService
{
    private const INTERCEPT = 15.5;
    private const COEFFICIENTS = [
        'kadar_hb' => -1.5,
        'kadar_mch' => -0.1,
        'kadar_mchc' => -0.1,
        'kadar_mcv' => -0.05,
    ];

    public function evaluate(array $input): array
    {
        if (!modelExecutionGatePassed()) {
            throw new RuntimeException('Research model is not enabled for use.');
        }

        $hb = $this->optionalNumber($input['kadar_hb'] ?? null, 0, 30);
        $mchc = $this->optionalNumber($input['kadar_mchc'] ?? null, 0, 100);
        $mcv = $this->optionalNumber($input['kadar_mcv'] ?? null, 0, 200);
        $mch = $this->optionalNumber($input['kadar_mch'] ?? null, 0, 100);
        $gejala = $this->requiredNumber($input['skor_gejala'] ?? null, 0, 100);
        $makan = $this->requiredNumber($input['skor_makan'] ?? null, 0, 18);
        $mens = (string) ($input['mens_teratur'] ?? '');
        if (!in_array($mens, ['ya', 'tidak'], true)) {
            throw new InvalidArgumentException('Menstruation input is invalid.');
        }

        if ($hb === null || $mch === null || $mchc === null || $mcv === null) {
            throw new InvalidArgumentException(
                'Hb, MCHC, MCV, dan MCH wajib lengkap untuk regresi logistik.'
            );
        }
        $z = self::INTERCEPT + (self::COEFFICIENTS['kadar_hb'] * $hb)
            + (self::COEFFICIENTS['kadar_mch'] * $mch)
            + (self::COEFFICIENTS['kadar_mchc'] * $mchc)
            + (self::COEFFICIENTS['kadar_mcv'] * $mcv);
        $probability = 1 / (1 + exp(-$z));

        $probability = max(0.0, min(0.99, $probability));
        return [
            'probability' => $probability,
            'category' => $probability < 0.33 ? 'rendah' : ($probability < 0.66 ? 'sedang' : 'tinggi'),
            'model_version' => requireEnvironmentValue('CLINICAL_MODEL_VERSION'),
            'model_checksum' => requireEnvironmentValue('CLINICAL_MODEL_CHECKSUM'),
        ];
    }

    /** @return array<string, mixed> */
    public function explainLogistic(array $input): array
    {
        $ranges = [
            'kadar_hb' => [0, 30, 'Hb', 'g/dL'],
            'kadar_mchc' => [0, 100, 'MCHC', 'g/dL'],
            'kadar_mcv' => [0, 200, 'MCV', 'fL'],
            'kadar_mch' => [0, 100, 'MCH', 'pg'],
        ];
        $values = [];
        foreach ($ranges as $key => [$min, $max]) {
            $values[$key] = $this->requiredNumber($input[$key] ?? null, $min, $max);
        }

        $z = self::INTERCEPT;
        $terms = [];
        foreach (self::COEFFICIENTS as $key => $coefficient) {
            $contribution = $coefficient * $values[$key];
            $z += $contribution;
            $terms[] = [
                'key' => $key,
                'label' => $ranges[$key][2],
                'unit' => $ranges[$key][3],
                'value' => $values[$key],
                'coefficient' => $coefficient,
                'contribution' => $contribution,
            ];
        }
        $probability = max(0.0, min(0.99, 1 / (1 + exp(-$z))));

        return [
            'status_label' => 'Simulasi Model Penelitian',
            'intercept' => self::INTERCEPT,
            'terms' => $terms,
            'z' => $z,
            'probability' => $probability,
            'category' => $probability < 0.33
                ? 'rendah'
                : ($probability < 0.66 ? 'sedang' : 'tinggi'),
            'equation' => 'P = 1 / (1 + e^-z)',
        ];
    }

    private function optionalNumber(mixed $value, float $min, float $max): ?float
    {
        if ($value === null || $value === '') return null;
        return $this->requiredNumber($value, $min, $max);
    }

    private function requiredNumber(mixed $value, float $min, float $max): float
    {
        if (is_array($value) || !is_numeric($value)) {
            throw new InvalidArgumentException('Clinical numeric input is invalid.');
        }
        $value = (float) $value;
        if (!is_finite($value) || $value < $min || $value > $max) {
            throw new InvalidArgumentException('Clinical numeric input is out of range.');
        }
        return $value;
    }
}
