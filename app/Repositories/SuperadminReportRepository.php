<?php

declare(strict_types=1);

final class SuperadminReportRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function paginate(string $role, int $page, int $perPage): array
    {
        if ($role !== '' && !in_array($role, ['siswa', 'uks', 'orangtua', 'superadmin'], true)) {
            throw new InvalidArgumentException('Unknown role filter.');
        }
        $where = " WHERE status <> 'archived'";
        $parameters = [];
        if ($role !== '') {
            $where .= ' AND role = ?';
            $parameters[] = $role;
        }
        $count = $this->pdo->prepare(
            'SELECT COUNT(*) FROM (SELECT role, status FROM users'
            . $where . ' GROUP BY role, status) grouped'
        );
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();
        $perPage = max(1, min(100, $perPage));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);

        $statement = $this->pdo->prepare(
            'SELECT role, status, COUNT(*) AS total FROM users'
            . $where
            . ' GROUP BY role, status ORDER BY role, status LIMIT ? OFFSET ?'
        );
        $position = 1;
        foreach ($parameters as $parameter) {
            $statement->bindValue($position++, $parameter, PDO::PARAM_STR);
        }
        $statement->bindValue($position++, $perPage, PDO::PARAM_INT);
        $statement->bindValue($position, ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();
        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        ];
    }

    public function exportRows(string $role): array
    {
        if ($role !== '' && !in_array($role, ['siswa', 'uks', 'orangtua', 'superadmin'], true)) {
            throw new InvalidArgumentException('Unknown role filter.');
        }
        $where = "status <> 'archived'";
        $parameters = [];
        if ($role !== '') {
            $where .= ' AND role = ?';
            $parameters[] = $role;
        }
        $statement = $this->pdo->prepare(
            'SELECT id, nama, username, role, status, kelas, created_at
             FROM users WHERE ' . $where . ' ORDER BY id LIMIT 1000'
        );
        $statement->execute($parameters);
        return $statement->fetchAll();
    }
}
