<?php

declare(strict_types=1);

final class QuestionnaireExport
{
    /** @var array<string, string> */
    private const ANSWER_COLUMNS = [
        'makan_1' => 'Pola Makan 1 - Sarapan pagi',
        'makan_2' => 'Pola Makan 2 - Rutin makan siang',
        'makan_3' => 'Pola Makan 3 - Selalu makan malam',
        'makan_4' => 'Pola Makan 4 - Snek pagi-siang',
        'makan_5' => 'Pola Makan 5 - Snek siang-malam',
        'makan_6' => 'Pola Makan 6 - Snek menjelang tidur',
        'gejala_1' => 'Gejala 1 - Cepat lelah bila beraktivitas',
        'gejala_2' => 'Gejala 2 - Merasa pusing',
        'gejala_3' => 'Gejala 3 - Mata berkunang-kunang',
        'gejala_4' => 'Gejala 4 - Ujung tangan/kaki sering dingin',
        'gejala_5' => 'Gejala 5 - Suka sempoyongan',
        'gejala_6' => 'Gejala 6 - Berdebar-debar saat aktivitas ringan',
        'gejala_7' => 'Gejala 7 - Sering mengantuk',
        'gejala_8' => 'Gejala 8 - Malas beraktivitas',
        'gejala_9' => 'Gejala 9 - Napas terasa pendek',
        'gejala_10' => 'Gejala 10 - Wajah terlihat pucat',
        'sikap_1' => 'Sikap 1 - Anemia merupakan keadaan di mana jumlah sel darah merah di bawah nilai normal',
        'sikap_2' => 'Sikap 2 - Anemia adalah penyakit kronis yang tidak dapat dicegah',
        'sikap_3' => 'Sikap 3 - Anemia dapat berdampak sangat serius terhadap tubuh',
        'sikap_4' => 'Sikap 4 - Anemia dapat berdampak terhadap masa depan generasi bangsa',
        'sikap_5' => 'Sikap 5 - Pola makan yang salah dapat menyebabkan anemia',
        'sikap_6' => 'Sikap 6 - Menstruasi yang tidak normal juga dapat menyebabkan anemia',
        'sikap_7' => 'Sikap 7 - Anemia tidak bisa disebabkan kecacingan',
        'sikap_8' => 'Sikap 8 - Konsumsi obat cacing setiap 6 bulan dapat mencegah anemia',
        'sikap_9' => 'Sikap 9 - Konsumsi TTD teratur dapat mencegah anemia',
        'sikap_10' => 'Sikap 10 - Pola makan tinggi zat besi dapat mencegah anemia',
        'pengetahuan_1' => 'Pengetahuan 1 - Apakah sahabat tahu tentang anemia?',
        'pengetahuan_2' => 'Pengetahuan 2 - Anemia adalah suatu keadaan',
        'pengetahuan_3' => 'Pengetahuan 3 - Penyebab anemia',
        'pengetahuan_4' => 'Pengetahuan 4 - Zat gizi yang sering menjadi penyebab anemia',
        'pengetahuan_5' => 'Pengetahuan 5 - Penyebab kekurangan zat gizi',
        'pengetahuan_6' => 'Pengetahuan 6 - Gejala anemia',
        'pengetahuan_7' => 'Pengetahuan 7 - Dampak anemia',
        'pengetahuan_8' => 'Pengetahuan 8 - Program pemerintah untuk mencegah anemia',
        'pengetahuan_9' => 'Pengetahuan 9 - Makanan tinggi zat besi',
        'pengetahuan_10' => 'Pengetahuan 10 - Kegunaan zat besi bagi tubuh',
    ];

    /** @param resource $stream @param list<array<string, mixed>> $rows */
    public function writeCsv($stream, array $rows): void
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('CSV stream is invalid.');
        }

        if (fwrite($stream, "\xEF\xBB\xBF") !== 3) {
            throw new RuntimeException('Unable to write the CSV byte-order mark.');
        }
        if (fputcsv(
            $stream,
            array_map('csvSafeCell', $this->headers()),
            ',',
            '"',
            ''
        ) === false) {
            throw new RuntimeException('Unable to write the CSV header.');
        }
        foreach ($rows as $row) {
            if (fputcsv(
                $stream,
                array_map('csvSafeCell', $this->row($row)),
                ',',
                '"',
                ''
            ) === false) {
                throw new RuntimeException('Unable to write a CSV row.');
            }
        }
    }

    /** @return list<string> */
    private function headers(): array
    {
        return [
            'ID Siswa',
            'Nama',
            'Username/NISN',
            'Kelas',
            'Tanggal Pengisian',
            ...array_values(self::ANSWER_COLUMNS),
            'Skor Keluhan (0-100)',
            'Skor Pola Makan (0-18)',
            'Skor Pengetahuan (0-53)',
            'Skor Sikap (0-40)',
            'Hb (g/dL)',
            'MCHC',
            'MCV',
            'MCH',
            'Probabilitas Risiko',
            'Kategori Risiko',
        ];
    }

    /** @return list<string|int|float> */
    private function row(array $row): array
    {
        $probability = $row['probabilitas_risiko'] ?? null;
        $answers = $this->answers($row['answers_snapshot'] ?? null);

        return [
            (int) ($row['student_id'] ?? 0),
            (string) ($row['nama'] ?? ''),
            $this->excelIdentifier($row['username'] ?? ''),
            (string) ($row['kelas'] ?? ''),
            (string) ($row['created_at'] ?? ''),
            ...array_map(
                static fn (string $key): string => $answers[$key] ?? '',
                array_keys(self::ANSWER_COLUMNS)
            ),
            (int) ($row['skor_gejala'] ?? 0),
            (int) ($row['skor_makan'] ?? 0),
            (int) ($row['skor_pengetahuan'] ?? 0),
            (int) ($row['skor_sikap'] ?? 0),
            $this->nullableNumber($row['kadar_hb'] ?? null),
            $this->nullableNumber($row['kadar_mchc'] ?? null),
            $this->nullableNumber($row['kadar_mcv'] ?? null),
            $this->nullableNumber($row['kadar_mch'] ?? null),
            $probability === null
                ? ''
                : number_format((float) $probability * 100, 1, ',', '.') . '%',
            $probability === null
                ? 'BELUM TERSEDIA'
                : strtoupper((string) ($row['kategori_risiko'] ?? '')),
        ];
    }

    /** @return array<string, string> */
    private function answers(mixed $snapshot): array
    {
        if (!is_string($snapshot) || $snapshot === '') {
            return [];
        }

        try {
            $decoded = json_decode($snapshot, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [];
        }
        if (!is_array($decoded['sections'] ?? null)) {
            return [];
        }

        $answers = [];
        foreach ($decoded['sections'] as $section) {
            if (!is_array($section) || !is_array($section['items'] ?? null)) {
                continue;
            }
            foreach ($section['items'] as $item) {
                if (!is_array($item) || !is_string($item['key'] ?? null)
                    || !is_string($item['answer'] ?? null)
                    || !array_key_exists($item['key'], self::ANSWER_COLUMNS)) {
                    continue;
                }

                $answer = $item['answer'];
                if (str_starts_with($item['key'], 'gejala_')
                    && preg_match('/^(\d{1,2}) dari 10$/', $answer, $matches) === 1) {
                    $answer = $matches[1];
                }
                $answers[$item['key']] = $answer;
            }
        }

        return $answers;
    }

    private function nullableNumber(mixed $value): string
    {
        return $value === null || $value === '' ? '' : (string) $value;
    }

    private function excelIdentifier(mixed $value): string
    {
        $value = (string) $value;
        return preg_match('/^\d+$/', $value) === 1 ? "\t" . $value : $value;
    }
}
