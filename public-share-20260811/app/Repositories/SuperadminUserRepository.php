<?php

declare(strict_types=1);

final class SuperadminUserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function paginate(
        string $search,
        string $role,
        string $status,
        int $page,
        int $perPage
    ): array {
        $search = trim($search);
        if (mb_strlen($search) > 100) {
            throw new InvalidArgumentException('Search is too long.');
        }
        if (
            $role !== ''
            && !in_array(
                $role,
                ['siswa', 'uks', 'orangtua', 'superadmin'],
                true
            )
        ) {
            throw new InvalidArgumentException('Unknown role filter.');
        }
        if (
            $status !== ''
            && !in_array($status, ['active', 'inactive', 'archived'], true)
        ) {
            throw new InvalidArgumentException('Unknown status filter.');
        }

        $conditions = [];
        $parameters = [];
        if ($search !== '') {
            $conditions[] = '(nama LIKE ? OR username LIKE ?)';
            $parameters[] = '%' . $search . '%';
            $parameters[] = '%' . $search . '%';
        }
        if ($role !== '') {
            $conditions[] = 'role = ?';
            $parameters[] = $role;
        }
        if ($status !== '') {
            $conditions[] = 'status = ?';
            $parameters[] = $status;
        }
        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

        $count = $this->pdo->prepare('SELECT COUNT(*) FROM users' . $where);
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();
        $perPage = max(1, min(100, $perPage));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);

        $statement = $this->pdo->prepare(
            'SELECT id, nama, role, status, username, kelas, created_at
             FROM users'
            . $where
            . ' ORDER BY created_at DESC, id DESC LIMIT ? OFFSET ?'
        );
        $position = 1;
        foreach ($parameters as $parameter) {
            $statement->bindValue($position++, $parameter, PDO::PARAM_STR);
        }
        $statement->bindValue($position++, $perPage, PDO::PARAM_INT);
        $statement->bindValue(
            $position,
            ($page - 1) * $perPage,
            PDO::PARAM_INT
        );
        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    public function findDetail(int $userId): ?array
    {
        if ($userId < 1) {
            return null;
        }
        $statement = $this->pdo->prepare(
            'SELECT id, nama, role, status, username, kelas, created_at,
                (SELECT COUNT(*) FROM kuesioner WHERE user_id = users.id)
                    AS questionnaires,
                (SELECT COUNT(*) FROM hasil_deteksi WHERE user_id = users.id)
                    AS risk_results,
                (SELECT COUNT(*) FROM konsumsi_ttd WHERE user_id = users.id)
                    AS ttd_records,
                (SELECT COUNT(*) FROM konsultasi WHERE siswa_id = users.id)
                    AS consultations
             FROM users
             WHERE id = ?'
        );
        $statement->execute([$userId]);
        $row = $statement->fetch();
        if (!$row) {
            return null;
        }

        $row['record_counts'] = [
            'questionnaires' => (int) $row['questionnaires'],
            'risk_results' => (int) $row['risk_results'],
            'ttd_records' => (int) $row['ttd_records'],
            'consultations' => (int) $row['consultations'],
        ];
        unset(
            $row['questionnaires'],
            $row['risk_results'],
            $row['ttd_records'],
            $row['consultations']
        );
        return $row;
    }
}
