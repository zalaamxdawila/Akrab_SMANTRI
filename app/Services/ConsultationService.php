<?php

declare(strict_types=1);

final class ConsultationService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function ask(int $studentId, string $question): void
    {
        $question = trim($question);
        if ($studentId <= 0 || $question === '' || mb_strlen($question) > 5000) {
            throw new InvalidArgumentException('Pertanyaan konsultasi tidak valid.');
        }
        $stmt = $this->pdo->prepare('INSERT INTO konsultasi (siswa_id, pertanyaan) VALUES (?, ?)');
        $stmt->execute([$studentId, $question]);
    }

    public function reply(int $uksId, int $consultationId, string $reply): void
    {
        $reply = trim($reply);
        if ($uksId <= 0 || $consultationId <= 0 || $reply === '' || mb_strlen($reply) > 5000) {
            throw new InvalidArgumentException('Balasan konsultasi tidak valid.');
        }

        $this->pdo->beginTransaction();
        try {
            $lock = $this->pdo->prepare(
                "SELECT k.id FROM konsultasi k
                 JOIN users u ON u.id = k.siswa_id AND u.role = 'siswa'
                 WHERE k.id = ? AND k.status = 'menunggu' FOR UPDATE"
            );
            $lock->execute([$consultationId]);
            if (!$lock->fetch()) {
                throw new DomainException('Consultation is unavailable.');
            }
            $insert = $this->pdo->prepare('INSERT INTO balasan_konsultasi (konsultasi_id, isi_balasan) VALUES (?, ?)');
            $insert->execute([$consultationId, $reply]);
            $update = $this->pdo->prepare("UPDATE konsultasi SET status = 'dijawab', ahli_id = ? WHERE id = ? AND status = 'menunggu'");
            $update->execute([$uksId, $consultationId]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
