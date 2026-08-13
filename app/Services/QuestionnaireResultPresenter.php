<?php

declare(strict_types=1);

final class QuestionnaireResultPresenter
{
    public function __construct(
        private QuestionnaireInsights $insights = new QuestionnaireInsights()
    ) {
    }

    /** @return array<string, mixed> */
    public function forResult(array $response, ?array $detection): array
    {
        $scores = $this->insights->forResponse($response);
        $priorities = [];
        foreach ($scores as $key => $score) {
            $concern = $key === 'gejala'
                ? (float) $score['percentage']
                : 100.0 - (float) $score['percentage'];
            $priorities[] = [
                'key' => $key,
                'label' => $score['label'],
                'level' => $score['level'],
                'explanation' => $score['explanation'],
                'concern' => round($concern, 1),
            ];
        }
        usort(
            $priorities,
            static fn (array $left, array $right): int =>
                $right['concern'] <=> $left['concern']
        );
        $priorities = array_slice($priorities, 0, 3);

        $risk = $this->risk($detection);
        $actions = [$this->riskAction($risk['key'])];
        foreach ($priorities as $priority) {
            $actions[] = $this->factorAction((string) $priority['key']);
        }

        return [
            'risk' => $risk,
            'scores' => $scores,
            'priorities' => $priorities,
            'actions' => array_values(array_unique($actions)),
            'answers' => $this->answers($response['answers_snapshot'] ?? null),
            'logistic' => $this->logistic($response),
            'disclaimer' => $this->insights->disclaimer(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function logistic(array $response): ?array
    {
        foreach (['kadar_hb', 'kadar_mchc', 'kadar_mcv', 'kadar_mch'] as $field) {
            if (($response[$field] ?? null) === null || $response[$field] === '') return null;
        }
        try {
            return (new AnemiaRiskService())->explainLogistic($response);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    /** @return array{key:string,label:string,tone:string,probability_label:string,date_label:string} */
    private function risk(?array $detection): array
    {
        if ($detection === null) {
            return [
                'key' => 'unknown',
                'label' => 'Belum tersedia',
                'tone' => 'secondary',
                'probability_label' => '-',
                'date_label' => '-',
            ];
        }

        $key = strtolower(trim((string) ($detection['kategori_risiko'] ?? '')));
        if (!in_array($key, ['rendah', 'sedang', 'tinggi'], true)) {
            $key = 'unknown';
        }
        $labels = [
            'rendah' => ['Rendah', 'success'],
            'sedang' => ['Sedang', 'warning'],
            'tinggi' => ['Tinggi', 'danger'],
            'unknown' => ['Belum tersedia', 'secondary'],
        ];
        $probability = max(
            0.0,
            min(1.0, (float) ($detection['probabilitas_risiko'] ?? 0))
        );
        $date = trim((string) ($detection['tanggal'] ?? ''));

        return [
            'key' => $key,
            'label' => $labels[$key][0],
            'tone' => $labels[$key][1],
            'probability_label' => number_format(
                $probability * 100,
                1,
                ',',
                '.'
            ) . '%',
            'date_label' => $date !== ''
                ? date('d M Y', strtotime($date))
                : '-',
        ];
    }

    private function riskAction(string $risk): string
    {
        return match ($risk) {
            'tinggi' => 'Segera diskusikan hasil dengan petugas UKS atau tenaga kesehatan untuk menentukan tindak lanjut.',
            'sedang' => 'Diskusikan hasil dengan petugas UKS dan pantau perubahan keluhan pada pengisian berikutnya.',
            'rendah' => 'Pertahankan kebiasaan baik dan tetap ikuti pemantauan kesehatan berkala.',
            default => 'Lengkapi skrining agar kategori risiko dan tindak lanjut dapat ditampilkan.',
        };
    }

    private function factorAction(string $key): string
    {
        return match ($key) {
            'gejala' => 'Catat keluhan yang dirasakan dan sampaikan bila menetap, memburuk, atau mengganggu aktivitas.',
            'makan' => 'Perkuat kebiasaan makan teratur dan ikuti saran gizi dari petugas kesehatan.',
            'pengetahuan' => 'Pelajari kembali materi anemia agar pilihan pencegahan lebih mudah diterapkan.',
            default => 'Diskusikan sikap dan kebiasaan pencegahan yang masih sulit diterapkan.',
        };
    }

    /** @return array{available:bool,message:string,version:?string,sections:array<string, mixed>} */
    private function answers(mixed $raw): array
    {
        if (!is_string($raw) || trim($raw) === '') {
            return [
                'available' => false,
                'message' => 'Rincian jawaban belum tersedia untuk pengisian lama ini.',
                'version' => null,
                'sections' => [],
            ];
        }

        try {
            $decoded = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
            if (!is_array($decoded)
                || !is_string($decoded['version'] ?? null)
                || !is_array($decoded['sections'] ?? null)
                || count($decoded['sections']) > 10) {
                throw new UnexpectedValueException('Invalid snapshot shape.');
            }

            $sections = [];
            foreach ($decoded['sections'] as $key => $section) {
                if (!is_string($key)
                    || !is_array($section)
                    || !is_string($section['label'] ?? null)
                    || mb_strlen($section['label']) > 200
                    || !is_array($section['items'] ?? null)
                    || count($section['items']) > 50) {
                    throw new UnexpectedValueException('Invalid snapshot section.');
                }
                $items = [];
                foreach ($section['items'] as $item) {
                    if (!is_array($item)
                        || !is_string($item['key'] ?? null)
                        || !is_string($item['question'] ?? null)
                        || !is_string($item['answer'] ?? null)
                        || mb_strlen($item['key']) > 80
                        || mb_strlen($item['question']) > 500
                        || mb_strlen($item['answer']) > 500) {
                        throw new UnexpectedValueException('Invalid snapshot item.');
                    }
                    $items[] = [
                        'key' => $item['key'],
                        'question' => $item['question'],
                        'answer' => $item['answer'],
                    ];
                }
                $sections[$key] = [
                    'label' => $section['label'],
                    'items' => $items,
                ];
            }

            return [
                'available' => true,
                'message' => '',
                'version' => $decoded['version'],
                'sections' => $sections,
            ];
        } catch (JsonException | UnexpectedValueException) {
            return [
                'available' => false,
                'message' => 'Rincian jawaban tidak dapat ditampilkan dengan aman.',
                'version' => null,
                'sections' => [],
            ];
        }
    }
}
