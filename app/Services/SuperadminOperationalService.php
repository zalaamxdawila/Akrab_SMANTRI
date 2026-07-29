<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/Security/ImpersonationMutationAudit.php';

abstract class SuperadminOperationalService
{
    private const REASONS = ['correction', 'verification', 'support', 'data_governance'];

    public function __construct(protected PDO $pdo)
    {
    }

    protected function assertActor(ActorContext $actor): void
    {
        if (!SuperadminGuard::contextIsAuthorized($actor)
            || !actionAllowedForActor($actor, 'manage_operations')) {
            throw new DomainException('Aksi master operasional ditolak.');
        }
    }

    protected function reason(string $reason): string
    {
        if (!in_array($reason, self::REASONS, true)) {
            throw new InvalidArgumentException('Alasan wajib dipilih.');
        }
        return $reason;
    }

    protected function lock(string $table, int $id): array
    {
        if ($id < 1) {
            throw new InvalidArgumentException('Target tidak valid.');
        }
        $sql = "SELECT * FROM {$table} WHERE id = ?";
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$id]);
        $record = $statement->fetch();
        if (!$record) {
            throw new DomainException('Data operasional tidak ditemukan.');
        }
        return $record;
    }

    protected function archiveRecord(
        ActorContext $actor,
        string $table,
        int $id,
        string $reason,
        string $requestId
    ): void {
        $this->assertActor($actor);
        $reason = $this->reason($reason);
        $this->pdo->beginTransaction();
        try {
            $record = $this->lock($table, $id);
            if ($record['archived_at'] !== null) {
                throw new DomainException('Data sudah diarsipkan.');
            }
            $update = $this->pdo->prepare(
                "UPDATE {$table} SET archived_at = CURRENT_TIMESTAMP,
                    archived_by = ?, archive_reason = ? WHERE id = ?"
            );
            $update->execute([$actor->authenticatedActorId, $reason, $id]);
            $this->audit($actor, 'operation.archived', $table, $id,
                $reason, $requestId, []);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    protected function correctRecord(
        ActorContext $actor,
        string $table,
        int $id,
        array $values,
        string $reason,
        string $requestId
    ): void {
        $this->assertActor($actor);
        $reason = $this->reason($reason);
        if ($values === []) {
            throw new InvalidArgumentException('Tidak ada koreksi.');
        }
        $this->pdo->beginTransaction();
        try {
            $record = $this->lock($table, $id);
            if ($record['archived_at'] !== null) {
                throw new DomainException('Data arsip tidak dapat dikoreksi.');
            }
            $sets = [];
            $parameters = [];
            foreach ($values as $field => $value) {
                $sets[] = "{$field} = ?";
                $parameters[] = $value;
            }
            $sets[] = 'corrected_at = CURRENT_TIMESTAMP';
            $sets[] = 'corrected_by = ?';
            $parameters[] = $actor->authenticatedActorId;
            $sets[] = 'correction_reason = ?';
            $parameters[] = $reason;
            $parameters[] = $id;
            $statement = $this->pdo->prepare(
                "UPDATE {$table} SET " . implode(', ', $sets) . ' WHERE id = ?'
            );
            $statement->execute($parameters);
            $this->audit($actor, 'operation.corrected', $table, $id,
                $reason, $requestId, array_keys($values));
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    protected function audit(
        ActorContext $actor,
        string $action,
        string $table,
        int $id,
        string $reason,
        string $requestId,
        array $fields
    ): void {
        (new ImpersonationMutationAudit($this->pdo))->record(
            $actor, $action, $table, $id, 'success', '/superadmin/operations',
            $requestId,
            ['reason_category' => $reason, 'changed_fields' => $fields]
        );
    }
}
