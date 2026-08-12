<?php

declare(strict_types=1);

require_once __DIR__ . '/SuperadminOperationalService.php';
require_once dirname(__DIR__, 2) . '/config/validation.php';

final class SuperadminConsultationService extends SuperadminOperationalService
{
    public function correctConsultation(
        ActorContext $actor,
        int $id,
        mixed $question,
        mixed $status,
        string $reason,
        string $requestId
    ): void {
        $this->assertActor($actor);
        $question = normalizeText($question, 5000);
        $status = enumValue($status, ['menunggu', 'dijawab']);
        if ($question === '') {
            throw new InvalidArgumentException('Pertanyaan wajib diisi.');
        }
        $reply = $this->pdo->prepare(
            'SELECT COUNT(*) FROM balasan_konsultasi
             WHERE konsultasi_id = ? AND archived_at IS NULL'
        );
        $reply->execute([$id]);
        $replyCount = (int) $reply->fetchColumn();
        if (($status === 'dijawab' && $replyCount < 1)
            || ($status === 'menunggu' && $replyCount > 0)) {
            throw new DomainException(
                'Status konsultasi tidak konsisten dengan balasan aktif.'
            );
        }
        $this->correctRecord($actor, 'konsultasi', $id, [
            'pertanyaan' => $question,
            'status' => $status,
        ], $reason, $requestId);
    }

    public function correctReply(
        ActorContext $actor,
        int $id,
        mixed $reply,
        string $reason,
        string $requestId
    ): void {
        $this->assertActor($actor);
        $reply = normalizeText($reply, 10000);
        if ($reply === '') {
            throw new InvalidArgumentException('Balasan wajib diisi.');
        }
        $this->correctRecord($actor, 'balasan_konsultasi', $id, [
            'isi_balasan' => $reply,
        ], $reason, $requestId);
    }

    public function archiveConsultation(
        ActorContext $actor,
        int $id,
        string $reason,
        string $requestId
    ): void {
        $this->assertActor($actor);
        $reason = $this->reason($reason);
        $this->pdo->beginTransaction();
        try {
            $record = $this->lock('konsultasi', $id);
            if ($record['archived_at'] !== null) {
                throw new DomainException('Konsultasi sudah diarsipkan.');
            }
            $this->pdo->prepare(
                'UPDATE konsultasi SET archived_at = CURRENT_TIMESTAMP,
                    archived_by = ?, archive_reason = ? WHERE id = ?'
            )->execute([$actor->authenticatedActorId, $reason, $id]);
            $this->pdo->prepare(
                'UPDATE balasan_konsultasi SET archived_at = CURRENT_TIMESTAMP,
                    archived_by = ?, archive_reason = ?
                 WHERE konsultasi_id = ? AND archived_at IS NULL'
            )->execute([$actor->authenticatedActorId, $reason, $id]);
            $this->audit($actor, 'operation.archived', 'konsultasi', $id,
                $reason, $requestId, []);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function archiveReply(
        ActorContext $actor,
        int $id,
        string $reason,
        string $requestId
    ): void {
        $this->archiveRecord(
            $actor, 'balasan_konsultasi', $id, $reason, $requestId
        );
    }
}
