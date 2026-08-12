<?php

declare(strict_types=1);

require_once __DIR__ . '/ActorContext.php';
require_once dirname(__DIR__, 2) . '/config/authorization.php';

final class ActorContextResolver
{
    public function __construct(private PDO $pdo)
    {
    }

    public function resolve(array $session, ?int $now = null): ActorContext
    {
        $userId = filter_var(
            $session['user_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($userId === false) {
            throw new DomainException('Authenticated session is invalid.');
        }

        if (!array_key_exists('_impersonation_session_id', $session)) {
            return $this->resolveNormalActor((int) $userId);
        }
        $impersonationId = filter_var(
            $session['_impersonation_session_id'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($impersonationId === false) {
            throw new DomainException('Impersonation session marker is invalid.');
        }

        return $this->resolveImpersonatedActor(
            (int) $userId,
            (int) $impersonationId,
            $now ?? time()
        );
    }

    private function resolveNormalActor(int $userId): ActorContext
    {
        $statement = $this->pdo->prepare(
            'SELECT id, role, status FROM users WHERE id = ?'
        );
        $statement->execute([$userId]);
        $user = $statement->fetch();

        if (
            !$user
            || $user['status'] !== 'active'
            || !in_array($user['role'], ['siswa', 'uks', 'orangtua', 'superadmin'], true)
            || (
                $user['role'] === 'superadmin'
                && !superadminFeatureEnabled()
            )
        ) {
            throw new DomainException('Authenticated actor is not available.');
        }

        return new ActorContext(
            (int) $user['id'],
            (int) $user['id'],
            (string) $user['role'],
            (string) $user['role']
        );
    }

    private function resolveImpersonatedActor(
        int $sessionUserId,
        int $impersonationId,
        int $now
    ): ActorContext {
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $expiryExpression = $driver === 'mysql'
            ? 'i.expires_at <= FROM_UNIXTIME(?)'
            : "CAST(strftime('%s', i.expires_at) AS INTEGER) <= ?";
        $statement = $this->pdo->prepare(
            "SELECT i.id, i.target_user_id, i.status,
                    {$expiryExpression} AS is_expired,
                    i.reason_category,
                    superadmin.id AS superadmin_id,
                    superadmin.role AS superadmin_role,
                    superadmin.status AS superadmin_status,
                    target.role AS target_role,
                    target.status AS target_status
             FROM impersonation_sessions i
             JOIN users superadmin ON superadmin.id = i.superadmin_id
             JOIN users target ON target.id = i.target_user_id
             WHERE i.id = ?"
        );
        $statement->execute([$now, $impersonationId]);
        $record = $statement->fetch();

        if (
            !$record
            || $record['status'] !== 'active'
            || (int) $record['is_expired'] === 1
            || $record['superadmin_role'] !== 'superadmin'
            || $record['superadmin_status'] !== 'active'
            || (
                !superadminFeatureEnabled()
            )
            || $record['target_status'] !== 'active'
            || !in_array($record['target_role'], ['siswa', 'uks', 'orangtua'], true)
            || (int) $record['target_user_id'] !== $sessionUserId
        ) {
            throw new DomainException('Impersonation session is not valid.');
        }

        return new ActorContext(
            (int) $record['superadmin_id'],
            (int) $record['target_user_id'],
            'superadmin',
            (string) $record['target_role'],
            (int) $record['id'],
            (string) $record['reason_category']
        );
    }
}
