<?php

declare(strict_types=1);

final class SuperadminOverviewRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function summary(string $today): array
    {
        $accounts = [];
        foreach (['siswa', 'uks', 'orangtua', 'superadmin'] as $role) {
            $accounts[$role] = [
                'active' => 0,
                'inactive' => 0,
                'archived' => 0,
            ];
        }
        $accountRows = $this->pdo->query(
            'SELECT role, status, COUNT(*) AS total
             FROM users
             GROUP BY role, status'
        )->fetchAll();
        foreach ($accountRows as $row) {
            if (
                isset($accounts[$row['role']])
                && array_key_exists($row['status'], $accounts[$row['role']])
            ) {
                $accounts[$row['role']][$row['status']] = (int) $row['total'];
            }
        }

        $parentLinks = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        $linkRows = $this->pdo->query(
            'SELECT status, COUNT(*) AS total
             FROM parent_student_links
             GROUP BY status'
        )->fetchAll();
        foreach ($linkRows as $row) {
            if (array_key_exists($row['status'], $parentLinks)) {
                $parentLinks[$row['status']] = (int) $row['total'];
            }
        }

        $ttd = $this->pdo->prepare(
            "SELECT COUNT(*) FROM konsumsi_ttd
             WHERE tanggal = ? AND status_konsumsi = 'sudah'"
        );
        $ttd->execute([$today]);

        return [
            'accounts' => $accounts,
            'parent_links' => $parentLinks,
            'operations' => [
                'consultations_total' => $this->count('konsultasi'),
                'consultations_pending' => $this->count(
                    'konsultasi',
                    "status = 'menunggu'"
                ),
                'articles' => $this->count('artikel_edukasi'),
                'ttd_confirmed_today' => (int) $ttd->fetchColumn(),
            ],
            'health' => [
                'questionnaires' => $this->count(
                    'kuesioner',
                    'archived_at IS NULL AND history_only_at IS NULL'
                ),
                'hb_records' => $this->count('kadar_hb'),
            ],
            'migration_version' => $this->latestMigrationVersion(),
        ];
    }

    private function count(string $table, ?string $condition = null): int
    {
        $allowed = [
            'konsultasi',
            'artikel_edukasi',
            'kuesioner',
            'kadar_hb',
        ];
        if (!in_array($table, $allowed, true)) {
            throw new InvalidArgumentException('Unsupported overview metric.');
        }

        $query = "SELECT COUNT(*) FROM {$table}";
        if ($condition !== null) {
            $query .= ' WHERE ' . $condition;
        }
        return (int) $this->pdo->query($query)->fetchColumn();
    }

    private function latestMigrationVersion(): ?string
    {
        $value = $this->pdo->query(
            'SELECT version FROM schema_migrations
             ORDER BY applied_at DESC, version DESC
             LIMIT 1'
        )->fetchColumn();

        return $value === false ? null : (string) $value;
    }
}
