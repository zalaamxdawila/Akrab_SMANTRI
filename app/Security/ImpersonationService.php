<?php

declare(strict_types=1);

require_once __DIR__ . '/ActorContext.php';
require_once __DIR__ . '/ImpersonationMutationAudit.php';
require_once dirname(__DIR__, 2) . '/config/authorization.php';

final class ImpersonationService
{
    private array $session;
    private Closure $regenerateSession;

    public function __construct(
        private PDO $pdo,
        array &$session,
        ?Closure $regenerateSession = null
    ) {
        $this->session =& $session;
        $this->regenerateSession = $regenerateSession
            ?? static function (): void {
                regenerateAuthenticatedSession();
            };
    }

    public function start(
        int $superadminId,
        string $password,
        int $targetUserId,
        string $reasonCategory,
        string $reasonNote,
        ?int $now = null
    ): int {
        $now ??= time();
        $reasonNote = trim($reasonNote);
        $this->assertStartInput($reasonCategory, $reasonNote);
        if (array_key_exists('_impersonation_session_id', $this->session)) {
            throw new DomainException('Nested impersonation is not permitted.');
        }
        if ((int) ($this->session['user_id'] ?? 0) !== $superadminId) {
            throw new DomainException('Authenticated superadmin does not match.');
        }
        if (!superadminFeatureEnabled()) {
            throw new DomainException('Superadmin feature is disabled.');
        }

        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $superadmin = $this->findUser($superadminId, true);
            $target = $this->findUser($targetUserId, true);
            if (
                !$superadmin
                || $superadmin['role'] !== 'superadmin'
                || $superadmin['status'] !== 'active'
                || !password_verify($password, $superadmin['password_hash'])
            ) {
                throw new DomainException('Step-up authentication failed.');
            }
            if (
                !$target
                || $target['status'] !== 'active'
                || !in_array($target['role'], ['siswa', 'uks', 'orangtua'], true)
            ) {
                throw new DomainException('Impersonation target is not available.');
            }

            $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            $timeValues = $driver === 'mysql'
                ? [$now, $now + 900]
                : [
                    gmdate('Y-m-d H:i:s', $now),
                    gmdate('Y-m-d H:i:s', $now + 900),
                ];
            $timePlaceholders = $driver === 'mysql'
                ? 'FROM_UNIXTIME(?), FROM_UNIXTIME(?)'
                : '?, ?';
            $statement = $this->pdo->prepare(
                "INSERT INTO impersonation_sessions (
                    superadmin_id, target_user_id, reason_category, reason_note,
                    started_at, expires_at, status
                 ) VALUES (?, ?, ?, ?, {$timePlaceholders}, 'active')"
            );
            $statement->execute([
                $superadminId,
                $targetUserId,
                $reasonCategory,
                $reasonNote,
                ...$timeValues,
            ]);
            $impersonationId = (int) $this->pdo->lastInsertId();

            $context = new ActorContext(
                $superadminId,
                $targetUserId,
                'superadmin',
                (string) $target['role'],
                $impersonationId,
                $reasonCategory
            );
            (new ImpersonationMutationAudit($this->pdo))->record(
                $context,
                'impersonation.started',
                'user',
                $targetUserId,
                'success',
                'server',
                $this->requestId(),
                ['reason_category' => $reasonCategory]
            );

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            ($this->regenerateSession)();
            $this->session['_impersonation_session_id'] = $impersonationId;
            $this->session['user_id'] = $targetUserId;
            $this->session['role'] = $target['role'];
            $this->session['nama'] = $target['nama'];

            return $impersonationId;
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function end(?int $now = null): void
    {
        $this->finish('ended', $now ?? time());
    }

    public function expireIfNeeded(?int $now = null): bool
    {
        $impersonationId = (int) (
            $this->session['_impersonation_session_id'] ?? 0
        );
        if ($impersonationId < 1) {
            return false;
        }

        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $expiryExpression = $driver === 'mysql'
            ? 'expires_at <= FROM_UNIXTIME(?)'
            : "CAST(strftime('%s', expires_at) AS INTEGER) <= ?";
        $statement = $this->pdo->prepare(
            "SELECT status, {$expiryExpression} AS is_expired
             FROM impersonation_sessions
             WHERE id = ?"
        );
        $statement->execute([$now ?? time(), $impersonationId]);
        $record = $statement->fetch();
        if (
            !$record
            || $record['status'] !== 'active'
            || (int) $record['is_expired'] === 1
        ) {
            $this->finish('expired', $now ?? time());
            return true;
        }

        return false;
    }

    private function finish(string $status, int $now): void
    {
        $impersonationId = (int) (
            $this->session['_impersonation_session_id'] ?? 0
        );
        if ($impersonationId < 1) {
            return;
        }

        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $statement = $this->pdo->prepare(
                'SELECT i.id, i.superadmin_id, i.target_user_id,
                        i.status AS impersonation_status,
                        i.reason_category,
                        superadmin.nama, superadmin.role, superadmin.status
                 FROM impersonation_sessions i
                 JOIN users superadmin ON superadmin.id = i.superadmin_id
                 WHERE i.id = ?'
            );
            $statement->execute([$impersonationId]);
            $record = $statement->fetch();
            if (
                !$record
                || $record['role'] !== 'superadmin'
                || $record['status'] !== 'active'
            ) {
                throw new DomainException('Original superadmin is not available.');
            }

            if ($record['impersonation_status'] === 'active') {
                $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
                $endedAt = $driver === 'mysql'
                    ? $now
                    : gmdate('Y-m-d H:i:s', $now);
                $endedAtPlaceholder = $driver === 'mysql'
                    ? 'FROM_UNIXTIME(?)'
                    : '?';
                $update = $this->pdo->prepare(
                    "UPDATE impersonation_sessions
                     SET status = ?, ended_at = {$endedAtPlaceholder}
                     WHERE id = ? AND status = 'active'"
                );
                $update->execute([
                    $status,
                    $endedAt,
                    $impersonationId,
                ]);

                $context = new ActorContext(
                    (int) $record['superadmin_id'],
                    (int) $record['target_user_id'],
                    'superadmin',
                    (string) ($this->session['role'] ?? ''),
                    $impersonationId,
                    (string) $record['reason_category']
                );
                (new ImpersonationMutationAudit($this->pdo))->record(
                    $context,
                    'impersonation.' . $status,
                    'user',
                    (int) $record['target_user_id'],
                    'success',
                    'server',
                    $this->requestId()
                );
            }

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            ($this->regenerateSession)();
            unset($this->session['_impersonation_session_id']);
            $this->session['user_id'] = (int) $record['superadmin_id'];
            $this->session['role'] = 'superadmin';
            $this->session['nama'] = $record['nama'];
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function findUser(int $userId, bool $forUpdate): array|false
    {
        $query = 'SELECT id, nama, role, status, password_hash FROM users WHERE id = ?';
        if ($forUpdate && $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $query .= ' FOR UPDATE';
        }
        $statement = $this->pdo->prepare($query);
        $statement->execute([$userId]);
        return $statement->fetch();
    }

    private function assertStartInput(
        string $reasonCategory,
        string $reasonNote
    ): void {
        if (
            !in_array(
                $reasonCategory,
                ['support', 'verification', 'training', 'incident_review'],
                true
            )
            || mb_strlen(trim($reasonNote)) < 5
            || mb_strlen(trim($reasonNote)) > 500
        ) {
            throw new InvalidArgumentException('Invalid impersonation reason.');
        }
    }

    private function requestId(): string
    {
        return function_exists('requestCorrelationId')
            ? requestCorrelationId()
            : '';
    }
}
