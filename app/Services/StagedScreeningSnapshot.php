<?php

declare(strict_types=1);

final class StagedScreeningSnapshot
{
    public const VERSION = '2026-08-30.staged-v1';

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
    private const DIET_QUESTIONS = [
        'Apakah sahabat ada sarapan setiap hari ?',
        'Apakah sahabat rutin makan siang ?',
        'Apakah sahabat selalu makan malam?',
        'Apakah sahabat ada makan snek antara makan pagi dan siang?',
        'Apakah sahabat ada makan snek antara makan siang dan malam?',
        'Apakah sahabat ada makan lagi atau snek menjelang tidur ?',
    ];

    /** @param array<string, mixed> $values @param array<string, mixed> $score */
    public function symptoms(array $values, array $score): string
    {
        $items = [];
        foreach (self::SYMPTOM_QUESTIONS as $offset => $question) {
            $value = (int) $values['symptoms'][$offset];
            $items[] = $this->item('gejala_' . ($offset + 1), $question, $value . ' dari 10', $value);
        }

        return $this->encode([
            'version' => self::VERSION,
            'profile' => [
                'tanggal_lahir' => $values['tanggal_lahir'],
                'usia' => $values['usia'],
                'pendidikan' => $values['pendidikan'],
                'jenis_kelamin' => $values['jenis_kelamin'],
            ],
            'scores' => [
                'symptom_total' => $score['total'],
                'symptom_average' => $score['average'],
                'risk_eligible' => $score['risk_eligible'],
            ],
            'sections' => [
                'gejala' => [
                    'label' => 'Keluhan dan gejala',
                    'items' => $items,
                ],
            ],
        ]);
    }

    /** @param array<string, mixed> $values @param array<string, mixed> $score */
    public function withRiskFactors(string $existing, array $values, array $score): string
    {
        try {
            $snapshot = json_decode($existing, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new InvalidArgumentException('Rincian jawaban gejala tidak valid.');
        }
        if (!is_array($snapshot) || ($snapshot['version'] ?? null) !== self::VERSION) {
            throw new InvalidArgumentException('Versi rincian jawaban tidak sesuai.');
        }

        $isMale = ($snapshot['profile']['jenis_kelamin'] ?? null) === 'laki_laki';
        $menstruationItems = [
            $this->item(
                'mens_sudah',
                'Apakah sahabat sudah mengalami menstruasi',
                $isMale ? 'Tidak berlaku' : ($values['mens_sudah'] === 'ya' ? 'Sudah' : 'Belum')
            ),
        ];
        if ($values['mens_sudah'] === 'ya') {
            $ageAnswer = $values['mens_usia_th'] . ' tahun, ' . $values['mens_usia_bln'] . ' bulan';
            $menstruationItems[] = $this->item('mens_usia', 'Usia pertama kali mengalami menstruasi', $ageAnswer);
            $menstruationItems[] = $this->item(
                'mens_teratur',
                'Apakah siklus menstruasi sahabat teratur tiap bulan?',
                $values['mens_teratur'] === 'ya' ? 'Ya' : 'Tidak'
            );
            $menstruationItems[] = $this->item(
                'mens_lama',
                'Berapa lama sahabat mengalami menstruasi setiap bulannya?',
                $values['mens_lama_hari'] . ' hari'
            );
            $menstruationItems[] = $this->item(
                'mens_jarak_siklus',
                'Berapa jarak antara siklus setiap bulannya?',
                $values['mens_jarak_siklus'] . ' hari'
            );
        }

        $dietItems = [];
        $mealLabels = ['pagi' => 'Pagi', 'jam_10' => 'Jam 10', 'siang' => 'Siang', 'jam_4' => 'Jam 4', 'malam' => 'Malam'];
        foreach ($mealLabels as $key => $label) {
            $meal = $values['meals'][$key];
            $answer = $meal['food'] !== '' ? $meal['food'] : '-';
            if ($meal['amount'] !== '') {
                $answer .= ' — ' . $meal['amount'];
            }
            $dietItems[] = $this->item('tabel_makan_' . $key, 'Makanan ' . $label, $answer);
        }
        $dietLabels = ['selalu' => 'Selalu', 'kadang' => 'Kadang-kadang', 'tidak' => 'Tidak pernah'];
        foreach (self::DIET_QUESTIONS as $offset => $question) {
            $key = 'makan_' . ($offset + 1);
            $value = $values['diet_habits'][$key];
            $dietItems[] = $this->item($key, $question, $dietLabels[$value]);
        }

        $snapshot['scores']['risk_internal_percentage'] = $score['internal_percentage'];
        $snapshot['scores']['risk_external_percentage'] = $score['external_percentage'];
        $snapshot['scores']['risk_factor_percentage'] = $score['percentage'];
        $snapshot['scores']['anemia_indicated'] = $score['anemia_indicated'];
        $snapshot['sections']['menstruasi'] = [
            'label' => 'Faktor risiko menstruasi',
            'items' => $menstruationItems,
        ];
        $snapshot['sections']['makan'] = [
            'label' => 'Pola makan sehari-hari',
            'items' => $dietItems,
        ];

        return $this->encode($snapshot);
    }

    /** @param array<string, mixed> $payload */
    private function encode(array $payload): string
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strlen($json) > 65535) {
            throw new InvalidArgumentException('Rincian jawaban melebihi batas.');
        }
        return $json;
    }

    /** @return array{key:string,question:string,answer:string,chart_value?:int} */
    private function item(string $key, string $question, string $answer, ?int $chartValue = null): array
    {
        $item = ['key' => $key, 'question' => $question, 'answer' => $answer];
        if ($chartValue !== null) {
            $item['chart_value'] = $chartValue;
        }
        return $item;
    }
}
