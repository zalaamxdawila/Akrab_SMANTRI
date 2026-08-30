<?php

declare(strict_types=1);

final class StagedScreeningResultPresenter
{
    /**
     * @param array<string, mixed> $screening
     * @return array{
     *   title:string,
     *   status_class:string,
     *   symptom_average:string,
     *   show_risk_score:bool,
     *   risk_percentage:?string,
     *   explanation:string,
     *   recommendations:list<string>,
     *   disclaimer:string
     * }
     */
    public function present(array $screening): array
    {
        $stage = (string) ($screening['tahap_screening'] ?? '');
        $symptomAverage = $this->boundedNumber($screening['rerata_gejala'] ?? null, 0, 10, 'Skor gejala');
        $disclaimer = 'Hasil ini adalah skrining awal tanpa pemeriksaan Hb, bukan diagnosis. '
            . 'Bahas hasil dengan ahli medis sekolah atau tenaga kesehatan.';

        if ($stage === 'gejala_selesai') {
            return [
                'title' => 'Skor gejala di bawah ambang',
                'status_class' => 'info',
                'symptom_average' => $this->format($symptomAverage),
                'show_risk_score' => false,
                'risk_percentage' => null,
                'explanation' => 'Rerata gejala tidak lebih dari 4,6, sehingga pertanyaan faktor risiko tidak dibuka. '
                    . 'Simpan hasil ini dan tetap pantau perubahan keluhan.',
                'recommendations' => [
                    'Pantau gejala dan ulangi skrining pada jadwal yang ditentukan sekolah.',
                    'Temui petugas UKS bila keluhan menetap, memburuk, atau mengganggu kegiatan belajar.',
                    'Cari pertolongan di Puskesmas atau dokter bila keluhan terasa berat.',
                ],
                'disclaimer' => $disclaimer,
            ];
        }

        if ($stage !== 'selesai') {
            throw new InvalidArgumentException('Skrining faktor risiko belum selesai.');
        }

        $riskPercentage = $this->boundedNumber(
            $screening['persentase_faktor_risiko'] ?? null,
            0,
            100,
            'Persentase faktor risiko'
        );
        $indicated = $riskPercentage < StagedScreeningScore::RISK_THRESHOLD;

        if ($indicated) {
            return [
                'title' => 'Terindikasi risiko anemia',
                'status_class' => 'danger',
                'symptom_average' => $this->format($symptomAverage),
                'show_risk_score' => true,
                'risk_percentage' => $this->format($riskPercentage) . '%',
                'explanation' => 'Rerata gejala melewati 4,6 dan skor faktor risiko berada di bawah 75%. '
                    . 'Pola ini menunjukkan indikasi risiko anemia yang perlu ditindaklanjuti.',
                'recommendations' => [
                    'Temui petugas UKS untuk meninjau jawaban dan kondisi Anda.',
                    'Ikuti arahan petugas UKS untuk pemeriksaan lanjutan di Puskesmas atau dokter.',
                    'Jika keluhan berat atau cepat memburuk, segera cari pertolongan tenaga kesehatan.',
                ],
                'disclaimer' => $disclaimer,
            ];
        }

        return [
            'title' => 'Belum terindikasi risiko anemia',
            'status_class' => 'success',
            'symptom_average' => $this->format($symptomAverage),
            'show_risk_score' => true,
            'risk_percentage' => $this->format($riskPercentage) . '%',
            'explanation' => 'Skor faktor risiko berada pada atau di atas 75%. Namun, karena rerata gejala '
                . 'melewati 4,6, tetap pantau keluhan dan jangan mengabaikan gejala yang menetap.',
            'recommendations' => [
                'Pertahankan pola makan teratur dan pantau perubahan gejala.',
                'Temui petugas UKS bila keluhan menetap atau mengganggu kegiatan belajar.',
                'Cari pertolongan di Puskesmas atau dokter bila keluhan terasa berat.',
            ],
            'disclaimer' => $disclaimer,
        ];
    }

    private function boundedNumber(mixed $value, float $min, float $max, string $label): float
    {
        if (!is_numeric($value)) {
            throw new InvalidArgumentException($label . ' tidak valid.');
        }
        $number = (float) $value;
        if (!is_finite($number) || $number < $min || $number > $max) {
            throw new InvalidArgumentException($label . ' di luar rentang.');
        }
        return $number;
    }

    private function format(float $number): string
    {
        return number_format($number, 1, ',', '.');
    }
}
