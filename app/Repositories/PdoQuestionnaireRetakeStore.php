<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Contracts/QuestionnaireRetakeStore.php';
require_once dirname(__DIR__) . '/Security/ImpersonationMutationAudit.php';

final class PdoQuestionnaireRetakeStore implements QuestionnaireRetakeStore
{
    public function __construct(private PDO $pdo)
    {
    }

    public function enableRetake(
        ActorContext $actor,
        int $studentId,
        string $reason,
        string $requestId
    ): int {
        $this->pdo->beginTransaction();
        try {
            $lock = $this->lockSuffix();
            $student = $this->pdo->prepare(
                "SELECT u.id FROM users u
                 WHERE u.id = ? AND u.role = 'siswa' AND u.status = 'active'"
                . $lock
            );
            $student->execute([$studentId]);
            if (!$student->fetchColumn()) {
                throw new DomainException('Siswa aktif tidak ditemukan.');
            }

            $current = $this->pdo->prepare(
                'SELECT id FROM kuesioner
                 WHERE user_id = ? AND archived_at IS NULL
                   AND history_only_at IS NULL
                 ORDER BY created_at, id' . $lock
            );
            $current->execute([$studentId]);
            $currentIds = array_map('intval', $current->fetchAll(PDO::FETCH_COLUMN));
            if ($currentIds === []) {
                throw new DomainException('Tidak ada kuesioner utama yang dapat direset.');
            }

            $this->rejectPendingLabChanges(
                $studentId,
                $actor->authenticatedActorId,
                $actor->effectiveRole
            );

            $update = $this->pdo->prepare(
                'UPDATE kuesioner
                 SET history_only_at = CURRENT_TIMESTAMP, history_only_by = ?,
                     history_only_reason = ?
                 WHERE user_id = ? AND archived_at IS NULL
                   AND history_only_at IS NULL'
            );
            $update->execute([
                $actor->authenticatedActorId,
                $reason,
                $studentId,
            ]);
            $updated = $update->rowCount();
            if ($updated !== count($currentIds)) {
                throw new RuntimeException('Status kuesioner berubah saat reset diproses.');
            }

            (new ImpersonationMutationAudit($this->pdo))->record(
                $actor,
                'questionnaire.retake_enabled',
                'student',
                $studentId,
                'success',
                '/staff/questionnaire-retake',
                $requestId,
                [
                    'changed_fields' => [
                        'history_only_at',
                        'history_only_by',
                        'history_only_reason',
                    ],
                ]
            );

            $this->pdo->commit();
            return $updated;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function rejectPendingLabChanges(
        int $studentId,
        int $reviewerId,
        string $reviewerRole
    ): void {
        $statement = $this->pdo->prepare(
            "UPDATE lab_change_requests
             SET status = 'rejected', reviewed_by = ?, reviewer_role = ?,
                 reviewed_at = CURRENT_TIMESTAMP
             WHERE student_id = ? AND status = 'pending'
               AND questionnaire_id IN (
                   SELECT id FROM kuesioner
                   WHERE user_id = ? AND archived_at IS NULL
                     AND history_only_at IS NULL
               )"
        );
        $statement->execute([
            $reviewerId,
            $reviewerRole,
            $studentId,
            $studentId,
        ]);
    }

    private function lockSuffix(): string
    {
        return $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'
            ? ' FOR UPDATE'
            : '';
    }
}
