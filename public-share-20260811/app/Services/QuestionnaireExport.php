<?php

declare(strict_types=1);

final class QuestionnaireExport
{
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
            'Skor Keluhan (0-100)',
            'Skor Pola Makan (0-18)',
            'Skor Pengetahuan (0-40)',
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

        return [
            (int) ($row['student_id'] ?? 0),
            (string) ($row['nama'] ?? ''),
            $this->excelIdentifier($row['username'] ?? ''),
            (string) ($row['kelas'] ?? ''),
            (string) ($row['created_at'] ?? ''),
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
