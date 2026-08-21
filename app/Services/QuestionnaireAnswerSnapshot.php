<?php

declare(strict_types=1);

final class QuestionnaireAnswerSnapshot
{
    public const VERSION = '2026-08-21.v5';

    /** @var list<string> */
    private const DIET_QUESTIONS = [
        'Apakah sahabat ada sarapan setiap hari ?',
        'Apakah sahabar rutin makan siang ?',
        'Apakah sahabat selalu makan malam?',
        'Apakah sahabat ada makan snek antara makan pagi dan siang?',
        'Apakah sahabat ada makan snek antara makan siang dan malam?',
        'Apakah sahabat ada makan lagi atau snek menjelang tidur ?',
    ];

    /** @var list<string> */
    private const SYMPTOM_QUESTIONS = [
        'Sahabat merasakan cepat lelah bila beraktivitas',
        'Sahabat merasakan pusing',
        'Sahabat merasakan mata berkunang-kunang',
        'Sahabat merasakan bagian ujung tangan atau kaki sering dingin',
        'Sahabat merasakan suka sempoyongan',
        'Sahabat merasakan berdebar-debar walaupun beraktivitas ringan',
        'Sahabat merasakan mengantuk',
        'Sahabat merasakan malas beraktivitas',
        'Sahabat merasakan nafas terasa pendek waktu beraktivitas',
        'Sahabat merasakan pucat',
    ];

    /** @var list<string> */
    private const ATTITUDE_QUESTIONS = [
        'Anemia merupakan keadaan dimana jumlah sel darah merah dibawah nilai normal',
        'Anemia adalah penyakit kronis yang tidak dapat dicegah',
        'Anemia dapat berdampak sangat serius terhadap tubuh',
        'Anemia dapat berdampak terhadap masa depan generasi bangsa',
        'Pola makan yang salah dapat menyebabkan anemia',
        'Menstruasi yang tidak normal juga dapat menyebabkan anemia',
        'Anemia tidak bisa disebabkan kecacingan',
        'Sebaiknya kita mengkonsumsi obat cacing untuk mencegah anemia setiap 6 bulan sekali',
        'Mengkonsumsi tablet tambah darah (TTD) secara teratur dapat mencegah anemia',
        'Pola makan tinggi zat besi dapat mencegah anemia',
    ];

    private const KNOWLEDGE_QUESTIONS = [
        1 => ['Apakah sahabat tahu tentang anemia?', ['Tahu, lanjut ke pertanyaan no 2', 'Tidak'], null],
        2 => ['Anemia adalah suatu keadaan :', ['Kurang darah', 'Kurang Hb dalam darah', 'Lain-lain'], 2],
        3 => ['Tahukan sahabat apa penyebab anemia?', ['Kurang zat gizi', 'Kelainan darah', 'Lain-lain'], 2],
        4 => ['Apa zat gizi yang sering menjadi penyebab anemia ?', ['Kurang zat besi (Fe)', 'Kurang asam folat', 'Kurang vitamin B12', 'Lain-lain'], 3],
        5 => ['Apakah yang menyebabkan sahabat mengalami kekurangan zat gizi tersebut ?', ['Siklus mentruasi tidak teratur', 'Pola makan yang tidak sesuai', 'Infeksi kecacingan', 'Persepsi diri yang salah', 'Lain-lain'], 4],
        6 => ['Apakah gejala anemia yang sahabat ketahui ?', ['Pusing', 'Pucat', 'Lemah dan lesu', 'Berdebar-debar', 'Nafas sering singkat', 'Cepat Lelah', 'Kaki dingin atau kebas', 'Mengantuk', 'Sempoyongan', 'Berkunang-kunang'], null],
        7 => ['Apakah dampak dari anemia', ['Prestasi sekolah menurun', 'Pertumbuhan terganggu', 'Tidak bugar', 'Mudah infeksi', 'Lain-lain'], 4],
        8 => ['Apakah program pemerintah untuk mencegah anemia', ['Pemberian Tablet Tambah Darah (TTD)', 'Penyuluhan tentang anemia', 'Tidak tahu', 'Lain-lain'], 3],
        9 => ['Apakah makanan yang tinggi kandungan zat besinya?', ['Hati ayam', 'Kuning telur', 'Daging sapi', 'Daging domba', 'Kacang-kacangan', 'Buah-buahan kering', 'Lain-lain'], 6],
        10 => ['Apa kegunaan zat besi bagi tubuh sahabat ?', ['Membentuk sel darah merah', 'Membentuk sel darah putih', 'Untuk daya tahan tubuh', 'Untuk pertumbuhan', 'Lain-lain'], 4],
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
            1 => 'Tidak Setuju',
            2 => 'Kurang Setuju',
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
            'menstruasi' => [
                'label' => 'Siklus menstruasi',
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
                $value === 'kadang' && $index >= 4 ? 'Kdang-kadang' : $dietAnswers[$value],
                ['tidak' => 1, 'kadang' => 2, 'selalu' => 3][$value]
            );
        }

        $menstruationAnswers = [
            ['mens_sudah', 'Apakah sahabat sudah mengalami menstruasi',
                enumValue($input['mens_sudah'] ?? null, ['ya', 'belum']) === 'ya' ? 'Sudah' : 'Belum'],
            ['mens_usia', 'Usia berapa sahabat mulai mengalami mentruasi?',
                (($input['mens_usia_th'] ?? '') !== '' ? boundedInt($input['mens_usia_th'], 5, 25) . ' Th' : '-')
                . (($input['mens_usia_bln'] ?? '') !== '' ? ', ' . boundedInt($input['mens_usia_bln'], 0, 11) . ' Bln' : '')],
            ['mens_teratur', 'Apakah siklus menstruasi sahabat teratur tiap bulan?',
                enumValue($input['mens_teratur'] ?? null, ['ya', 'tidak']) === 'ya' ? 'Ya' : 'Tidak'],
            ['mens_lama', 'Berama lama sahabat mengalami menstruasi setiap bulannya?',
                (($input['mens_lama'] ?? '') !== '' ? boundedInt($input['mens_lama'], 1, 15) . ' hr' : '-')],
            ['mens_jarak_siklus', 'Berapa jarak antara siklus setiap bulannya?',
                (($input['mens_jarak_siklus'] ?? '') !== '' ? boundedInt($input['mens_jarak_siklus'], 1, 100) . ' hari' : '-')],
        ];
        foreach ($menstruationAnswers as [$key, $question, $answer]) {
            $sections['menstruasi']['items'][] = $this->item($key, $question, $answer);
        }

        $foodItems = [];
        foreach (['pagi' => 'Pagi', 'jam_10' => 'Jam 10', 'siang' => 'Siang', 'jam_4' => 'Jam 4', 'malam' => 'Malam'] as $key => $label) {
            $food = normalizeText($input['makanan_' . $key] ?? '', 150);
            $amount = normalizeText($input['jumlah_' . $key] ?? '', 80);
            $foodItems[] = $this->item(
                'tabel_makan_' . $key,
                'Makanan ' . $label,
                ($food !== '' ? $food : '-') . ($amount !== '' ? ' — ' . $amount : '')
            );
        }
        $sections['makan']['items'] = [...$foodItems, ...$sections['makan']['items']];

        foreach (self::SYMPTOM_QUESTIONS as $offset => $question) {
            $index = $offset + 1;
            $value = boundedInt($input['gejala_' . $index] ?? null, 0, 10);
            $sections['gejala']['items'][] = $this->item(
                'gejala_' . $index,
                $question,
                $value . ' dari 10',
                $value
            );
        }

        foreach (self::ATTITUDE_QUESTIONS as $offset => $question) {
            $index = $offset + 1;
            $value = boundedInt($input['sikap_' . $index] ?? null, 1, 4);
            $sections['sikap']['items'][] = $this->item(
                'sikap_' . $index,
                $question,
                $attitudeAnswers[$value],
                $value
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
                $answerLabels ? implode(', ', $answerLabels) : 'Tidak memilih jawaban',
                count($selected)
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

    /** @return array{key:string,question:string,answer:string,chart_value?:int} */
    private function item(
        string $key,
        string $question,
        string $answer,
        ?int $chartValue = null
    ): array
    {
        $item = ['key' => $key, 'question' => $question, 'answer' => $answer];
        if ($chartValue !== null) {
            $item['chart_value'] = $chartValue;
        }
        return $item;
    }
}
