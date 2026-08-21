<?php

declare(strict_types=1);

final class QuestionnaireAnswerSnapshot
{
    public const VERSION = '2026-08-21.v3';

    /** @var list<string> */
    private const FACTOR_INTERNAL_QUESTIONS = [
        'Riwayat anemia sebelumnya',
        'Riwayat gangguan pencernaan',
        'Konsumsi suplemen zat besi',
        'Riwayat alergi makanan tertentu',
        'Gangguan penyerapan zat gizi',
    ];

    /** @var list<string> */
    private const FACTOR_EXTERNAL_QUESTIONS = [
        'Asupan zat besi dari makanan sehari-hari',
        'Frekuensi konsumsi makanan tinggi kalsium',
        'Pendapatan keluarga',
        'Asupan vitamin C',
        'Partisipasi dalam edukasi kesehatan',
    ];

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
        'Anemia merupakan keadaan di mana jumlah sel darah merah di bawah nilai normal',
        'Anemia adalah penyakit kronis yang tidak dapat dicegah',
        'Anemia dapat berdampak sangat serius terhadap tubuh',
        'Anemia dapat berdampak terhadap masa depan generasi bangsa',
        'Pola makan yang salah dapat menyebabkan anemia',
        'Menstruasi yang tidak normal juga dapat menyebabkan anemia',
        'Anemia tidak bisa disebabkan kecacingan',
        'Sebaiknya kita mengonsumsi obat cacing untuk mencegah anemia setiap 6 bulan sekali',
        'Mengonsumsi Tablet Tambah Darah (TTD) secara teratur dapat mencegah anemia',
        'Pola makan tinggi zat besi dapat mencegah anemia',
    ];

    private const KNOWLEDGE_QUESTIONS = [
        1 => ['Apakah sahabat tahu tentang anemia?', ['Tahu, lanjut ke pertanyaan no. 2', 'Tidak', 'Lain-lain'], 2],
        2 => ['Anemia adalah suatu keadaan:', ['Kurang darah', 'Kurang Hb dalam darah', 'Lain-lain', 'Tidak tahu'], 2],
        3 => ['Tahukah sahabat apa penyebab anemia?', ['Kurang zat gizi', 'Kelainan darah', 'Lain-lain', 'Tidak tahu'], 2],
        4 => ['Apa zat gizi yang sering menjadi penyebab anemia?', ['Kurang zat besi (Fe)', 'Kurang asam folat', 'Kurang vitamin B12', 'Lain-lain', 'Tidak tahu'], 3],
        5 => ['Apakah yang menyebabkan sahabat mengalami kekurangan zat gizi tersebut?', ['Siklus menstruasi tidak teratur', 'Pola makan yang tidak sesuai', 'Infeksi kecacingan', 'Persepsi diri yang salah', 'Lain-lain'], 4],
        6 => ['Apakah gejala anemia yang sahabat ketahui?', ['Pusing', 'Pucat', 'Lemah dan lesu', 'Berdebar-debar', 'Napas sering singkat', 'Cepat lelah', 'Kaki dingin atau kebas', 'Mengantuk', 'Sempoyongan', 'Berkunang-kunang'], null],
        7 => ['Apakah dampak dari anemia?', ['Prestasi sekolah menurun', 'Pertumbuhan terganggu', 'Tidak bugar', 'Mudah infeksi', 'Lain-lain', 'Tidak tahu'], 4],
        8 => ['Apakah program pemerintah untuk mencegah anemia?', ['Pemberian Tablet Tambah Darah (TTD)', 'Penyuluhan tentang anemia', 'Tidak tahu', 'Lain-lain', 'Tidak ada'], 3],
        9 => ['Apakah makanan yang tinggi kandungan zat besinya?', ['Hati ayam', 'Kuning telur', 'Daging sapi', 'Daging domba', 'Kacang-kacangan', 'Buah-buahan kering', 'Lain-lain'], 6],
        10 => ['Apa kegunaan zat besi bagi tubuh sahabat?', ['Membentuk sel darah merah', 'Membentuk sel darah putih', 'Untuk daya tahan tubuh', 'Untuk pertumbuhan', 'Lain-lain'], 4],
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
            'faktor_internal' => [
                'label' => 'Faktor risiko internal',
                'items' => [],
            ],
            'faktor_eksternal' => [
                'label' => 'Faktor risiko eksternal',
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

        foreach (self::FACTOR_INTERNAL_QUESTIONS as $offset => $question) {
            $index = $offset + 1;
            $value = enumValue(
                $input['faktor_internal_' . $index] ?? null,
                ['ya', 'tidak']
            );
            $sections['faktor_internal']['items'][] = $this->item(
                'faktor_internal_' . $index,
                $question,
                $value === 'ya' ? 'Ya' : 'Tidak'
            );
        }

        $externalLabels = [
            'rendah' => 'Rendah',
            'sedang' => 'Sedang',
            'tinggi' => 'Tinggi',
        ];
        foreach (self::FACTOR_EXTERNAL_QUESTIONS as $offset => $question) {
            $index = $offset + 1;
            $value = enumValue(
                $input['faktor_eksternal_' . $index] ?? null,
                array_keys($externalLabels)
            );
            $sections['faktor_eksternal']['items'][] = $this->item(
                'faktor_eksternal_' . $index,
                $question,
                $externalLabels[$value]
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

        foreach (self::KNOWLEDGE_QUESTIONS as $index => [$question, $labels, $otherIndex]) {
            $knowledgeLabels = [];
            foreach ($labels as $offset => $label) {
                $knowledgeLabels[chr(ord('a') + $offset)] = $label;
            }
            $selected = $input['pengetahuan_' . $index] ?? [];
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
            $other = normalizeText($input['pengetahuan_' . $index . '_other'] ?? '', 200);
            $answerLabels = array_map(function (string $answer) use ($knowledgeLabels, $otherIndex, $other): string {
                $label = $knowledgeLabels[$answer];
                if ($otherIndex !== null && $answer === chr(ord('a') + $otherIndex)) {
                    return $other === '' ? $label : $label . ': ' . $other;
                }
                return $label;
            }, $selected);
            $sections['pengetahuan']['items'][] = $this->item(
                'pengetahuan_' . $index,
                $question,
                $answerLabels ? implode(', ', $answerLabels) : 'Tidak memilih jawaban'
            );
        }

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
