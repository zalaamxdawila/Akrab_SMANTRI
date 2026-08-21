<?php

declare(strict_types=1);

final class QuestionnaireInsights
{
    /**
     * @return array<string, array{field:string,label:string,max:float,direction:string}>
     */
    public function definitions(): array
    {
        return [
            'gejala' => [
                'field' => 'skor_gejala',
                'label' => 'Keluhan & gejala',
                'max' => 100.0,
                'direction' => 'lower',
            ],
            'makan' => [
                'field' => 'skor_makan',
                'label' => 'Pola makan',
                'max' => 18.0,
                'direction' => 'higher',
            ],
            'pengetahuan' => [
                'field' => 'skor_pengetahuan',
                'label' => 'Pengetahuan anemia',
                'max' => 53.0,
                'direction' => 'higher',
            ],
            'sikap' => [
                'field' => 'skor_sikap',
                'label' => 'Sikap & kesadaran',
                'max' => 40.0,
                'direction' => 'higher',
            ],
            'faktor_internal' => [
                'field' => 'skor_faktor_internal',
                'label' => 'Faktor risiko internal',
                'max' => 5.0,
                'direction' => 'risk',
            ],
            'faktor_eksternal' => [
                'field' => 'skor_faktor_eksternal',
                'label' => 'Faktor risiko eksternal',
                'max' => 15.0,
                'direction' => 'higher',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $response
     * @return array<string, array{
     *   label:string,value:float,max:float,percentage:float,level:string,
     *   tone:string,explanation:string
     * }>
     */
    public function forResponse(array $response): array
    {
        $results = [];
        foreach ($this->definitions() as $key => $definition) {
            $value = max(
                0.0,
                min($definition['max'], (float) ($response[$definition['field']] ?? 0))
            );
            $percentage = round(($value / $definition['max']) * 100, 1);

            if ($definition['direction'] === 'lower') {
                [$level, $tone, $explanation] = $this->symptomExplanation($percentage);
            } elseif ($definition['direction'] === 'risk') {
                [$level, $tone, $explanation] = $this->riskFactorExplanation($key, $percentage);
            } else {
                [$level, $tone, $explanation] = $this->protectiveExplanation(
                    $key,
                    $percentage
                );
            }

            $results[$key] = [
                'label' => $definition['label'],
                'value' => $value,
                'max' => $definition['max'],
                'percentage' => $percentage,
                'level' => $level,
                'tone' => $tone,
                'explanation' => $explanation,
            ];
        }

        return $results;
    }

    /**
     * @param list<array<string, mixed>> $history
     * @return array{labels:list<string>,series:array<string,list<float>>}
     */
    public function historyChart(array $history): array
    {
        $chart = [
            'labels' => [],
            'series' => [
                'gejala' => [],
                'makan' => [],
                'pengetahuan' => [],
                'sikap' => [],
                'faktor_internal' => [],
                'faktor_eksternal' => [],
            ],
        ];
        foreach ($history as $response) {
            $chart['labels'][] = date(
                'd M Y',
                strtotime((string) $response['created_at'])
            );
            foreach ($this->forResponse($response) as $key => $insight) {
                $chart['series'][$key][] = $insight['percentage'];
            }
        }

        return $chart;
    }

    public function disclaimer(): string
    {
        return 'Ringkasan ini adalah hasil skrining, bukan diagnosis medis. '
            . 'Hubungi petugas UKS atau tenaga kesehatan untuk penilaian dan tindak lanjut.';
    }

    /** @return array{string,string,string} */
    private function symptomExplanation(float $percentage): array
    {
        if ($percentage >= 67) {
            return [
                'Keluhan tinggi',
                'danger',
                'Jawaban menunjukkan lebih banyak keluhan yang dirasakan. '
                    . 'Perlu ditinjau bersama petugas UKS.',
            ];
        }
        if ($percentage >= 34) {
            return [
                'Keluhan sedang',
                'warning',
                'Ada beberapa keluhan yang dilaporkan. Pantau perubahan dan '
                    . 'diskusikan bila menetap.',
            ];
        }

        return [
            'Keluhan rendah',
            'success',
            'Keluhan yang dilaporkan relatif sedikit pada pengisian ini.',
        ];
    }

    /** @return array{string,string,string} */
    private function protectiveExplanation(string $key, float $percentage): array
    {
        $subject = match ($key) {
            'makan' => 'kebiasaan makan pendukung pencegahan anemia',
            'pengetahuan' => 'pemahaman tentang anemia',
            default => 'sikap dan kesadaran terhadap pencegahan anemia',
        };

        if ($percentage >= 75) {
            return ['Baik', 'success', ucfirst($subject) . ' berada pada tingkat baik.'];
        }
        if ($percentage >= 50) {
            return [
                'Cukup',
                'warning',
                ucfirst($subject) . ' cukup, tetapi masih dapat diperkuat.',
            ];
        }

        return [
            'Perlu penguatan',
            'danger',
            ucfirst($subject) . ' perlu mendapat perhatian dan edukasi tambahan.',
        ];
    }

    /** @return array{string,string,string} */
    private function riskFactorExplanation(string $key, float $percentage): array
    {
        $subject = $key === 'faktor_internal'
            ? 'faktor risiko internal yang teridentifikasi'
            : 'kondisi pelindung eksternal';

        if ($percentage >= 67) {
            $level = $key === 'faktor_internal' ? 'Banyak faktor risiko' : 'Kurang mendukung';
            return [
                $level,
                'danger',
                ucfirst($subject) . ' menunjukkan perlunya perhatian lebih.',
            ];
        }
        if ($percentage >= 34) {
            return [
                'Sedang',
                'warning',
                ucfirst($subject) . ' pada tingkat sedang, pantau secara berkala.',
            ];
        }

        $level = $key === 'faktor_internal' ? 'Sedikit faktor risiko' : 'Cukup mendukung';
        return [
            $level,
            'success',
            ucfirst($subject) . ' relatif baik.',
        ];
    }
}
