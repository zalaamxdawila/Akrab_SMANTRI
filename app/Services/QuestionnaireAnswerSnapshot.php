<?php

declare(strict_types=1);

final class QuestionnaireAnswerSnapshot
{
    public const VERSION = '2026-08-17.v1';

    /** @var list<string> */
    private const DIET_QUESTIONS = [
        'Sarapan pagi',
        'Rutin makan siang',
        'Selalu makan malam',
        'Snek pagi-siang',
        'Snek siang-malam',
        'Snek menjelang tidur',
    ];

    /** @var list<string> */
    private const SYMPTOM_QUESTIONS = [
        'Cepat lelah bila beraktivitas',
        'Merasa pusing',
        'Mata berkunang-kunang',
        'Ujung tangan/kaki sering dingin',
        'Suka sempoyongan',
        'Berdebar-debar saat aktivitas ringan',
        'Sering mengantuk',
        'Malas beraktivitas',
        'Napas terasa pendek',
        'Wajah terlihat pucat',
    ];

    /** @var list<string> */
    private const ATTITUDE_QUESTIONS = [
        'Anemia merupakan kondisi sel darah merah di bawah normal',
        'Anemia kronis tidak dapat dicegah',
        'Anemia berdampak sangat serius bagi kesehatan',
        'Anemia berdampak terhadap masa depan bangsa',
        'Pola makan salah adalah penyebab utama anemia',
    ];

    /**
     * @return array{
     *   version:string,
     *   sections:array<string, array{label:string,items:list<array{key:string,question:string,answer:string}>}>
     * }
     */
    public function fromInput(array $input): array
    {
        $dietAnswers = [
            'selalu' => 'Selalu',
            'kadang' => 'Kadang-kadang',
            'tidak' => 'Tidak pernah',
        ];
        $attitudeAnswers = [
            1 => 'Sangat Tidak Setuju',
            2 => 'Tidak Setuju',
            3 => 'Setuju',
            4 => 'Sangat Setuju',
        ];

        $sections = [
            'makan' => [
                'label' => 'Pola makan sehari-hari',
                'items' => [],
            ],
            'gejala' => [
                'label' => 'Keluhan dan gejala',
                'items' => [],
            ],
            'sikap' => [
                'label' => 'Opini terhadap anemia',
                'items' => [],
            ],
            'pengetahuan' => [
                'label' => 'Pengetahuan dasar',
                'items' => [],
            ],
        ];

        foreach (self::DIET_QUESTIONS as $offset => $question) {
            $index = $offset + 1;
            $value = enumValue(
                $input['makan_' . $index] ?? null,
                array_keys($dietAnswers)
            );
            $sections['makan']['items'][] = $this->item(
                'makan_' . $index,
                $question,
                $dietAnswers[$value]
            );
        }

        foreach (self::SYMPTOM_QUESTIONS as $offset => $question) {
            $index = $offset + 1;
            $value = boundedInt($input['gejala_' . $index] ?? null, 0, 10);
            $sections['gejala']['items'][] = $this->item(
                'gejala_' . $index,
                $question,
                $value . ' dari 10'
            );
        }

        foreach (self::ATTITUDE_QUESTIONS as $offset => $question) {
            $index = $offset + 1;
            $value = boundedInt($input['sikap_' . $index] ?? null, 1, 4);
            $sections['sikap']['items'][] = $this->item(
                'sikap_' . $index,
                $question,
                $attitudeAnswers[$value]
            );
        }

        $knowledgeLabels = [
            'a' => 'Zat Besi (Fe)',
            'b' => 'Asam Folat',
        ];
        $selected = $input['pengetahuan_1'] ?? [];
        if (!is_array($selected) || count($selected) > count($knowledgeLabels)) {
            throw new InvalidArgumentException('Jawaban pengetahuan tidak valid.');
        }
        $selected = array_values(array_unique($selected));
        foreach ($selected as $answer) {
            if (!is_string($answer) || !array_key_exists($answer, $knowledgeLabels)) {
                throw new InvalidArgumentException('Jawaban pengetahuan tidak valid.');
            }
        }
        sort($selected);
        $answerLabels = array_map(
            static fn (string $answer): string => $knowledgeLabels[$answer],
            $selected
        );
        $sections['pengetahuan']['items'][] = $this->item(
            'pengetahuan_1',
            'Zat gizi apa yang menjadi penyebab utama anemia?',
            $answerLabels ? implode(', ', $answerLabels) : 'Tidak memilih jawaban'
        );

        return [
            'version' => self::VERSION,
            'sections' => $sections,
        ];
    }

    public function encode(array $input): string
    {
        $json = json_encode(
            $this->fromInput($input),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if (strlen($json) > 65535) {
            throw new InvalidArgumentException('Rincian jawaban melebihi batas.');
        }

        return $json;
    }

    /** @return array{key:string,question:string,answer:string} */
    private function item(string $key, string $question, string $answer): array
    {
        return ['key' => $key, 'question' => $question, 'answer' => $answer];
    }
}
