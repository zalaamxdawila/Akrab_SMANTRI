<?php

declare(strict_types=1);

function normalizeText(mixed $value, int $maxLength): string
{
    if (is_array($value) || is_object($value)) {
        throw new InvalidArgumentException('Format input tidak valid.');
    }
    $text = trim((string) $value);
    if (mb_strlen($text) > $maxLength) {
        throw new InvalidArgumentException('Panjang input melebihi batas.');
    }
    return $text;
}

function optionalDate(mixed $value, bool $allowFuture = false): ?string
{
    if (is_array($value) || is_object($value)) {
        throw new InvalidArgumentException('Tanggal tidak valid.');
    }
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    $valid = $date && $date->format('Y-m-d') === $value;
    if (!$valid || (!$allowFuture && $date > new DateTimeImmutable('today'))) {
        throw new InvalidArgumentException('Tanggal tidak valid.');
    }
    return $value;
}

function optionalDecimal(mixed $value, float $min, float $max): ?float
{
    if (is_array($value) || is_object($value)) {
        throw new InvalidArgumentException('Nilai numerik tidak valid.');
    }
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    if (!is_numeric($value)) {
        throw new InvalidArgumentException('Nilai numerik tidak valid.');
    }
    $number = (float) $value;
    if (!is_finite($number) || $number < $min || $number > $max) {
        throw new InvalidArgumentException('Nilai numerik di luar rentang.');
    }
    return $number;
}

function boundedInt(mixed $value, int $min, int $max, bool $optional = false): ?int
{
    if (is_array($value) || is_object($value)) {
        throw new InvalidArgumentException('Bilangan bulat tidak valid.');
    }
    $value = trim((string) $value);
    if ($optional && $value === '') {
        return null;
    }
    if (!preg_match('/^-?\d+$/', $value)) {
        throw new InvalidArgumentException('Bilangan bulat tidak valid.');
    }
    $number = (int) $value;
    if ($number < $min || $number > $max) {
        throw new InvalidArgumentException('Bilangan bulat di luar rentang.');
    }
    return $number;
}

function enumValue(mixed $value, array $allowed, bool $optional = false): ?string
{
    if (is_array($value) || is_object($value)) {
        throw new InvalidArgumentException('Pilihan input tidak valid.');
    }
    $value = trim((string) $value);
    if ($optional && $value === '') {
        return null;
    }
    if (!in_array($value, $allowed, true)) {
        throw new InvalidArgumentException('Pilihan input tidak valid.');
    }
    return $value;
}

function validateBmiInput(mixed $weight, mixed $height): array
{
    try {
        $weight = optionalDecimal($weight, 1, 500);
        $height = optionalDecimal($height, 30, 250);
        if ($weight === null || $height === null) {
            throw new InvalidArgumentException('Berat dan tinggi wajib diisi.');
        }
        return ['valid' => true, 'weight' => $weight, 'height' => $height, 'error' => null];
    } catch (InvalidArgumentException $exception) {
        return ['valid' => false, 'weight' => null, 'height' => null, 'error' => $exception->getMessage()];
    }
}

function validateQuestionnaireInput(array $input): array
{
    try {
        $labStatus = enumValue($input['lab_status'] ?? null, ['tersedia', 'belum_ada']);
        $labValues = [
            'kadar_hb' => null,
            'kadar_mchc' => null,
            'kadar_mcv' => null,
            'kadar_mch' => null,
        ];

        if ($labStatus === 'tersedia') {
            $labValues = [
                'kadar_hb' => optionalDecimal($input['kadar_hb'] ?? null, 0, 30),
                'kadar_mchc' => optionalDecimal($input['kadar_mchc'] ?? null, 0, 100),
                'kadar_mcv' => optionalDecimal($input['kadar_mcv'] ?? null, 0, 200),
                'kadar_mch' => optionalDecimal($input['kadar_mch'] ?? null, 0, 100),
            ];
            if (in_array(null, $labValues, true)) {
                throw new InvalidArgumentException(
                    'Semua nilai hasil lab darah wajib diisi jika hasil tersedia.'
                );
            }
        }

        $values = [
            'tanggal_wawancara' => optionalDate($input['tanggal_wawancara'] ?? null),
            'inisial_responden' => normalizeText($input['inisial'] ?? '', 20),
            'tanggal_lahir' => optionalDate($input['tanggal_lahir'] ?? null),
            'tempat_lahir' => normalizeText($input['tempat_lahir'] ?? '', 100),
            'alamat' => normalizeText($input['alamat'] ?? '', 5000),
            'pendidikan' => enumValue($input['pendidikan'] ?? null, ['Kelas VII', 'Kelas VIII', 'Kelas IX', 'Kelas X', 'Kelas XI', 'Kelas XII'], true),
            'jurusan' => normalizeText($input['jurusan'] ?? '', 80),
            ...$labValues,
            'mens_sudah' => enumValue($input['mens_sudah'] ?? null, ['ya', 'belum']),
            'mens_usia_th' => boundedInt($input['mens_usia_th'] ?? null, 5, 25, true),
            'mens_teratur' => enumValue($input['mens_teratur'] ?? null, ['ya', 'tidak']),
            'mens_lama_hari' => boundedInt($input['mens_lama'] ?? null, 1, 15, true),
            'mens_jarak_siklus' => boundedInt($input['mens_jarak_siklus'] ?? null, 1, 100, true),
            'makanan_dikonsumsi' => normalizeText($input['makanan_dikonsumsi'] ?? '', 1000),
        ];

        $gejala = 0;
        for ($i = 1; $i <= 10; $i++) {
            $gejala += (int) boundedInt($input['gejala_' . $i] ?? null, 0, 10);
        }
        $sikap = 0;
        for ($i = 1; $i <= 10; $i++) {
            $sikap += (int) boundedInt($input['sikap_' . $i] ?? null, 0, 4);
        }
        $makan = 0;
        foreach (range(1, 6) as $i) {
            $makan += match (enumValue($input['makan_' . $i] ?? null, ['selalu', 'kadang', 'tidak'])) {
                'selalu' => 3,
                'kadang' => 2,
                'tidak' => 1,
            };
        }
        $pengetahuan = 0;
        for ($i = 1; $i <= 10; $i++) {
            $answers = $input['pengetahuan_' . $i] ?? [];
            if (!is_array($answers) || count($answers) > 4) {
                throw new InvalidArgumentException('Jawaban pengetahuan tidak valid.');
            }
            foreach ($answers as $answer) {
                if (!in_array($answer, ['a', 'b', 'c', 'd'], true)) {
                    throw new InvalidArgumentException('Jawaban pengetahuan tidak valid.');
                }
            }
            $pengetahuan += count(array_unique($answers));
        }
        $values['skor_gejala'] = $gejala;
        $values['skor_sikap'] = $sikap;
        $values['skor_pengetahuan'] = $pengetahuan;
        $values['skor_makan'] = $makan;
        return ['valid' => true, 'errors' => [], 'values' => $values];
    } catch (InvalidArgumentException $exception) {
        return ['valid' => false, 'errors' => [$exception->getMessage()], 'values' => []];
    }
}
