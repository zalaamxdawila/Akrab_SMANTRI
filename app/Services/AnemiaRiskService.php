<?php

declare(strict_types=1);

final class AnemiaRiskService
{
    public function evaluate(array $input): array
    {
        if (!clinicalApprovalGatePassed()) {
            throw new RuntimeException('Clinical model is not approved for use.');
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

        if ($hb !== null) {
            $z = 15.0 + 0.5 - (1.5 * $hb)
                - (0.1 * ($mch ?? 29.5))
                - (0.1 * ($mchc ?? 33.2))
                - (0.05 * ($mcv ?? 90.0));
            $probability = 1 / (1 + exp(-$z));
        } else {
            $risk = 0.1;
            if ($gejala > 50) $risk += 0.4;
            elseif ($gejala > 25) $risk += 0.2;
            if ($makan < 9) $risk += 0.3;
            elseif ($makan < 14) $risk += 0.1;
            if ($mens === 'tidak') $risk += 0.15;
            $probability = min($risk, 0.99);
        }

        $probability = max(0.0, min(0.99, $probability));
        return [
            'probability' => $probability,
            'category' => $probability < 0.33 ? 'rendah' : ($probability < 0.66 ? 'sedang' : 'tinggi'),
            'model_version' => requireEnvironmentValue('CLINICAL_MODEL_VERSION'),
            'model_checksum' => requireEnvironmentValue('CLINICAL_MODEL_CHECKSUM'),
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
