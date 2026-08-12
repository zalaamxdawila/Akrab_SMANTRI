<?php

declare(strict_types=1);

const AKRAB_CSV_MAX_BYTES = 2_000_000;
const AKRAB_CSV_MAX_ROWS = 1_000;
const AKRAB_CSV_MAX_LINE_LENGTH = 4_096;

function csvSafeCell(mixed $value): string
{
    $value = (string) $value;
    return preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1
        ? "'" . $value
        : $value;
}

function csvHeaderIsValid(array $header): bool
{
    $normalized = array_map(static fn ($value): string => strtolower(trim((string) $value)), $header);
    return $normalized === ['nama', 'kelas', 'username', 'password'];
}

function csvStudentRow(array $row): array
{
    if (count($row) !== 4) {
        throw new InvalidArgumentException('Jumlah kolom harus tepat 4.');
    }
    [$name, $class, $username, $password] = array_map(static fn ($value): string => trim((string) $value), $row);
    if ($name === '' || strlen($name) > 100 || $class === '' || strlen($class) > 20) {
        throw new InvalidArgumentException('Nama atau kelas tidak valid.');
    }
    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
        throw new InvalidArgumentException('Username tidak valid.');
    }
    if (strlen($password) < 8 || strlen($password) > 128) {
        throw new InvalidArgumentException('Password harus 8 sampai 128 karakter.');
    }
    return [$name, $class, $username, $password];
}
