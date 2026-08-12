<?php

declare(strict_types=1);

final class SuperadminParentLinkRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function paginate(
        string $search,
        string $status,
        bool $includeArchived,
        int $page,
        int $perPage
    ): array {
        $search = trim($search);
        if (mb_strlen($search) > 100) {
            throw new InvalidArgumentException('Pencarian terlalu panjang.');
        }
        if ($status !== '' && !in_array($status, ['pending', 'approved', 'rejected'], true)) {
            throw new InvalidArgumentException('Status tidak valid.');
        }
        $where = [$includeArchived ? 'psl.archived_at IS NOT NULL' : 'psl.archived_at IS NULL'];
        $parameters = [];
        if ($search !== '') {
            $where[] = '(p.nama LIKE ? OR p.username LIKE ? OR s.nama LIKE ?
                OR s.username LIKE ? OR psl.requested_student_username LIKE ?)';
            for ($i = 0; $i < 5; $i++) {
                $parameters[] = '%' . $search . '%';
            }
        }
        if ($status !== '') {
            $where[] = 'psl.status = ?';
            $parameters[] = $status;
        }
        $from = ' FROM parent_student_links psl
            JOIN users p ON p.id = psl.parent_id AND p.role = \'orangtua\'
            LEFT JOIN users s ON s.id = psl.student_id AND s.role = \'siswa\'';
        $condition = ' WHERE ' . implode(' AND ', $where);
        $count = $this->pdo->prepare('SELECT COUNT(*)' . $from . $condition);
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();
        $perPage = max(1, min(100, $perPage));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $statement = $this->pdo->prepare(
            'SELECT psl.id, psl.status, psl.requested_student_username,
                psl.requested_at, psl.reviewed_at, psl.archived_at,
                p.nama parent_name, p.username parent_username,
                s.nama student_name, s.username student_username'
            . $from . $condition
            . ' ORDER BY psl.requested_at DESC, psl.id DESC LIMIT ? OFFSET ?'
        );
        $position = 1;
        foreach ($parameters as $parameter) {
            $statement->bindValue($position++, $parameter);
        }
        $statement->bindValue($position++, $perPage, PDO::PARAM_INT);
        $statement->bindValue($position, ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();
        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }
}
