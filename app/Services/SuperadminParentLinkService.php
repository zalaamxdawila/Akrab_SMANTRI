<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/Security/ImpersonationMutationAudit.php';

final class SuperadminParentLinkService
{
    private const ACTIONS = ['approve', 'reject', 'correct', 'archive', 'restore'];
    private const REASONS = ['correction', 'verification', 'support', 'data_governance'];

    public function __construct(private PDO $pdo)
    {
    }

    public function apply(
        ActorContext $actor,
        int $linkId,
        string $action,
        string $studentUsername,
        string $reason,
        string $requestId
    ): void {
        if (!SuperadminGuard::contextIsAuthorized($actor)
            || !actionAllowedForActor($actor, 'manage_parent_links')) {
            throw new DomainException('Aksi superadmin ditolak.');
        }
        if ($linkId < 1 || !in_array($action, self::ACTIONS, true)
            || !in_array($reason, self::REASONS, true)) {
            throw new InvalidArgumentException('Aksi atau alasan tidak valid.');
        }
        $this->pdo->beginTransaction();
        try {
            $sql = 'SELECT psl.*, p.role parent_role
                FROM parent_student_links psl
                JOIN users p ON p.id = psl.parent_id
                WHERE psl.id = ?';
            if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
                $sql .= ' FOR UPDATE';
            }
            $lock = $this->pdo->prepare($sql);
            $lock->execute([$linkId]);
            $link = $lock->fetch();
            if (!$link || $link['parent_role'] !== 'orangtua') {
                throw new DomainException('Relasi tidak ditemukan.');
            }

            if ($action === 'archive') {
                if ($link['archived_at'] !== null) {
                    throw new DomainException('Relasi sudah diarsipkan.');
                }
                $update = $this->pdo->prepare(
                    'UPDATE parent_student_links SET archived_at = CURRENT_TIMESTAMP,
                        archived_by = ?, archive_reason = ? WHERE id = ?'
                );
                $update->execute([$actor->authenticatedActorId, $reason, $linkId]);
            } elseif ($action === 'restore') {
                if ($link['archived_at'] === null) {
                    throw new DomainException('Relasi tidak sedang diarsipkan.');
                }
                $update = $this->pdo->prepare(
                    'UPDATE parent_student_links SET archived_at = NULL,
                        archived_by = NULL, archive_reason = NULL WHERE id = ?'
                );
                $update->execute([$linkId]);
            } else {
                if ($link['archived_at'] !== null) {
                    throw new DomainException('Relasi arsip tidak dapat diubah.');
                }
                $studentUsername = trim($studentUsername);
                $studentId = null;
                if ($action !== 'reject') {
                    $student = $this->pdo->prepare(
                        "SELECT id FROM users
                         WHERE username = ? AND role = 'siswa' AND status = 'active'"
                    );
                    $student->execute([$studentUsername]);
                    $studentId = $student->fetchColumn();
                    if (!$studentId) {
                        throw new DomainException('Target siswa aktif tidak ditemukan.');
                    }
                }
                $status = $action === 'reject' ? 'rejected'
                    : ($action === 'approve' ? 'approved' : $link['status']);
                $requested = $action === 'reject'
                    ? $link['requested_student_username']
                    : $studentUsername;
                $update = $this->pdo->prepare(
                    'UPDATE parent_student_links SET student_id = ?,
                        requested_student_username = ?, status = ?,
                        reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP WHERE id = ?'
                );
                $update->execute([
                    $studentId ?: null,
                    $requested,
                    $status,
                    $actor->authenticatedActorId,
                    $linkId,
                ]);
            }
            (new ImpersonationMutationAudit($this->pdo))->record(
                $actor,
                'parent_link.' . $action,
                'parent_student_link',
                $linkId,
                'success',
                '/superadmin/parent_link_action.php',
                $requestId,
                ['reason_category' => $reason]
            );
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
