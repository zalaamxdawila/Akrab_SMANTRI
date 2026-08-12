<?php

declare(strict_types=1);

final class SuperadminAuditRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function paginate(array $filters, int $page, int $perPage): array
    {
        $conditions = [];
        $parameters = [];
        $this->integerFilter(
            $filters,
            'authenticated_actor_id',
            'COALESCE(a.authenticated_actor_id, a.actor_id)',
            $conditions,
            $parameters
        );
        $this->integerFilter(
            $filters,
            'effective_actor_id',
            'COALESCE(a.effective_actor_id, a.actor_id)',
            $conditions,
            $parameters
        );

        $action = trim((string) ($filters['action'] ?? ''));
        if (strlen($action) > 80) {
            throw new InvalidArgumentException('Action filter is too long.');
        }
        if ($action !== '') {
            $conditions[] = 'a.action = ?';
            $parameters[] = $action;
        }

        $requestId = trim((string) ($filters['request_id'] ?? ''));
        if (
            strlen($requestId) > 64
            || ($requestId !== ''
                && !preg_match('/\A[A-Za-z0-9._-]+\z/D', $requestId))
        ) {
            throw new InvalidArgumentException('Request ID filter is invalid.');
        }
        if ($requestId !== '') {
            $conditions[] = 'a.request_id = ?';
            $parameters[] = $requestId;
        }

        $jsonOutcome = $this->jsonValueExpression('a.metadata_json', 'outcome');
        $outcome = trim((string) ($filters['outcome'] ?? ''));
        if (
            $outcome !== ''
            && !in_array(
                $outcome,
                ['started', 'success', 'failed', 'forbidden'],
                true
            )
        ) {
            throw new InvalidArgumentException('Outcome filter is invalid.');
        }
        if ($outcome !== '') {
            $conditions[] = $jsonOutcome . ' = ?';
            $parameters[] = $outcome;
        }

        $dateFrom = $this->validDate($filters['date_from'] ?? '');
        $dateTo = $this->validDate($filters['date_to'] ?? '');
        if ($dateFrom !== null) {
            $conditions[] = 'a.created_at >= ?';
            $parameters[] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo !== null) {
            $conditions[] = 'a.created_at <= ?';
            $parameters[] = $dateTo . ' 23:59:59';
        }
        if ($dateFrom !== null && $dateTo !== null && $dateFrom > $dateTo) {
            throw new InvalidArgumentException('Audit date range is invalid.');
        }

        $where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
        $count = $this->pdo->prepare(
            'SELECT COUNT(*) FROM audit_log a' . $where
        );
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();
        $perPage = max(1, min(100, $perPage));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $jsonRoute = $this->jsonValueExpression('a.metadata_json', 'route');

        $statement = $this->pdo->prepare(
            "SELECT a.id,
                    COALESCE(a.authenticated_actor_id, a.actor_id)
                        AS authenticated_actor_id,
                    COALESCE(a.effective_actor_id, a.actor_id)
                        AS effective_actor_id,
                    a.impersonation_session_id, a.request_id, a.action,
                    a.target_type, a.target_id, a.created_at,
                    authenticated.nama AS authenticated_name,
                    effective.nama AS effective_name,
                    {$jsonOutcome} AS outcome,
                    {$jsonRoute} AS route
             FROM audit_log a
             LEFT JOIN users authenticated
                ON authenticated.id =
                    COALESCE(a.authenticated_actor_id, a.actor_id)
             LEFT JOIN users effective
                ON effective.id =
                    COALESCE(a.effective_actor_id, a.actor_id)"
            . $where
            . ' ORDER BY a.created_at DESC, a.id DESC LIMIT ? OFFSET ?'
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

    private function integerFilter(
        array $filters,
        string $key,
        string $column,
        array &$conditions,
        array &$parameters
    ): void {
        $raw = $filters[$key] ?? '';
        if ($raw === '' || $raw === null) {
            return;
        }
        $value = filter_var(
            $raw,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($value === false) {
            throw new InvalidArgumentException('Actor filter is invalid.');
        }
        $conditions[] = $column . ' = (? + 0)';
        $parameters[] = (int) $value;
    }

    private function validDate(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Audit date filter is invalid.');
        }
        return $value;
    }

    private function jsonValueExpression(string $column, string $key): string
    {
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            return "JSON_UNQUOTE(JSON_EXTRACT({$column}, '$.{$key}'))";
        }
        return "json_extract({$column}, '$.{$key}')";
    }
}
