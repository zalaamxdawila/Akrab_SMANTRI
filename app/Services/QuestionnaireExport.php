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

    /** @var array<string, string> */
    private const STAGED_ANSWER_COLUMNS = [
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
        'mens_sudah' => 'Menstruasi - Status',
        'mens_usia' => 'Menstruasi - Usia pertama',
        'mens_teratur' => 'Menstruasi - Siklus teratur',
        'mens_lama' => 'Menstruasi - Lama setiap bulan',
        'mens_jarak_siklus' => 'Menstruasi - Jarak siklus',
        'tabel_makan_pagi' => 'Makanan - Pagi',
        'tabel_makan_jam_10' => 'Makanan - Jam 10',
        'tabel_makan_siang' => 'Makanan - Siang',
        'tabel_makan_jam_4' => 'Makanan - Jam 4',
        'tabel_makan_malam' => 'Makanan - Malam',
        'makan_1' => 'Pola Makan 1 - Sarapan pagi',
        'makan_2' => 'Pola Makan 2 - Rutin makan siang',
        'makan_3' => 'Pola Makan 3 - Selalu makan malam',
        'makan_4' => 'Pola Makan 4 - Snek pagi-siang',
        'makan_5' => 'Pola Makan 5 - Snek siang-malam',
        'makan_6' => 'Pola Makan 6 - Snek menjelang tidur',
    ];

    /** @param resource $stream @param list<array<string, mixed>> $rows */
    public function writeLegacyCsv($stream, array $rows): void
    {
        $this->writeCsv($stream, $this->legacyHeaders(), $rows, $this->legacyRow(...));
    }

    /** @param resource $stream @param list<array<string, mixed>> $rows */
    public function writeStagedCsv($stream, array $rows): void
    {
        $this->writeCsv($stream, $this->stagedHeaders(), $rows, $this->stagedRow(...));
    }

    /**
     * @param resource $stream
     * @param list<string> $headers
     * @param list<array<string, mixed>> $rows
     * @param callable(array<string, mixed>):list<string|int|float> $rowFormatter
     */
    private function writeCsv($stream, array $headers, array $rows, callable $rowFormatter): void
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('CSV stream is invalid.');
        }

        if (fwrite($stream, "\xEF\xBB\xBF") !== 3) {
            throw new RuntimeException('Unable to write the CSV byte-order mark.');
        }
        if (fputcsv(
            $stream,
            array_map('csvSafeCell', $headers),
            ',',
            '"',
            ''
        ) === false) {
            throw new RuntimeException('Unable to write the CSV header.');
        }
        foreach ($rows as $row) {
            if (fputcsv(
                $stream,
                array_map('csvSafeCell', $rowFormatter($row)),
                ',',
                '"',
                ''
            ) === false) {
                throw new RuntimeException('Unable to write a CSV row.');
            }
        }
    }

    /** @return list<string> */
    private function legacyHeaders(): array
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
            'Skor Pengetahuan (0-48)',
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
    private function legacyRow(array $row): array
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

    /** @return list<string> */
    private function stagedHeaders(): array
    {
        return [
            'ID Siswa',
            'Nama',
            'Username/NISN',
            'Kelas Saat Pengisian',
            'Tanggal Lahir',
            'Usia Saat Pengisian',
            'Jenis Kelamin',
            'Tanggal Pengisian',
            'Tahap Skrining',
            ...array_values(self::STAGED_ANSWER_COLUMNS),
            'Rerata Gejala (0-10)',
            'Persentase Faktor Risiko',
            'Hasil Skrining',
            'Versi Skrining',
        ];
    }

    /** @return list<string|int|float> */
    private function stagedRow(array $row): array
    {
        $snapshot = $this->snapshot($row['answers_snapshot'] ?? null);
        $profile = is_array($snapshot['profile'] ?? null) ? $snapshot['profile'] : [];
        $answers = $this->answersFromSnapshot($snapshot);
        $stage = (string) ($row['tahap_screening'] ?? '');
        $age = $profile['usia'] ?? null;

        return [
            (int) ($row['student_id'] ?? 0),
            (string) ($row['nama'] ?? ''),
            $this->excelIdentifier($row['username'] ?? ''),
            (string) ($row['pendidikan'] ?? $profile['pendidikan'] ?? $row['kelas'] ?? ''),
            (string) ($row['tanggal_lahir'] ?? $profile['tanggal_lahir'] ?? ''),
            is_numeric($age) ? (int) $age . ' tahun' : '',
            $this->genderLabel($row['jenis_kelamin'] ?? $profile['jenis_kelamin'] ?? null),
            (string) ($row['created_at'] ?? ''),
            $this->stageLabel($stage),
            ...array_map(
                static fn (string $key): string => $answers[$key] ?? '',
                array_keys(self::STAGED_ANSWER_COLUMNS)
            ),
            $this->nullableNumber($row['rerata_gejala'] ?? null),
            $stage === 'selesai'
                ? $this->nullableNumber($row['persentase_faktor_risiko'] ?? null)
                : '',
            $this->screeningOutcomeLabel($row['hasil_screening'] ?? null),
            (string) ($row['versi_screening'] ?? $snapshot['version'] ?? ''),
        ];
    }

    /** @return array<string, string> */
    private function answers(mixed $snapshot): array
    {
        return $this->answersFromSnapshot($this->snapshot($snapshot));
    }

    /** @return array<string, mixed> */
    private function snapshot(mixed $snapshot): array
    {
        if (!is_string($snapshot) || $snapshot === '') {
            return [];
        }

        try {
            $decoded = json_decode($snapshot, true, 64, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (JsonException) {
            return [];
        }
    }

    /** @param array<string, mixed> $snapshot @return array<string, string> */
    private function answersFromSnapshot(array $snapshot): array
    {
        if (!is_array($snapshot['sections'] ?? null)) {
            return [];
        }

        $answers = [];
        foreach ($snapshot['sections'] as $section) {
            if (!is_array($section) || !is_array($section['items'] ?? null)) {
                continue;
            }
            foreach ($section['items'] as $item) {
                if (!is_array($item) || !is_string($item['key'] ?? null)
                    || !is_string($item['answer'] ?? null)
                    || (!array_key_exists($item['key'], self::ANSWER_COLUMNS)
                        && !array_key_exists($item['key'], self::STAGED_ANSWER_COLUMNS))) {
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

    private function stageLabel(string $stage): string
    {
        return match ($stage) {
            'gejala_selesai' => 'TAHAP GEJALA SELESAI',
            'faktor_risiko_tersedia' => 'MENUNGGU FAKTOR RISIKO',
            'selesai' => 'SKRINING SELESAI',
            default => '',
        };
    }

    private function screeningOutcomeLabel(mixed $outcome): string
    {
        return match ($outcome) {
            'gejala_di_bawah_ambang' => 'GEJALA DI BAWAH AMBANG',
            'terindikasi_anemia' => 'TERINDIKASI RISIKO ANEMIA',
            'tidak_terindikasi_anemia' => 'TIDAK TERINDIKASI RISIKO ANEMIA',
            default => '',
        };
    }

    private function genderLabel(mixed $gender): string
    {
        return match ($gender) {
            'perempuan' => 'Perempuan',
            'laki_laki' => 'Laki-laki',
            default => '',
        };
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
