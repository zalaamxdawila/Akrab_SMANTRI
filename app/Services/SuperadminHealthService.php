<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/Security/ImpersonationMutationAudit.php';
require_once dirname(__DIR__, 2) . '/config/validation.php';

final class SuperadminHealthService
{
    private const TABLES = [
        'kuesioner',
        'hasil_deteksi',
        'kadar_hb',
        'konsumsi_ttd',
        'riwayat_haid',
    ];
    private const REASONS = ['correction', 'verification', 'support', 'data_governance'];

    public function __construct(private PDO $pdo)
    {
    }

    public function correctQuestionnaire(
        ActorContext $actor,
        int $id,
        array $input,
        string $reason,
        string $requestId
    ): void {
        $allowed = [
            'kadar_hb' => fn ($v) => optionalDecimal($v, 0, 30),
            'kadar_mchc' => fn ($v) => optionalDecimal($v, 0, 100),
            'kadar_mcv' => fn ($v) => optionalDecimal($v, 0, 200),
            'kadar_mch' => fn ($v) => optionalDecimal($v, 0, 100),
            'skor_gejala' => fn ($v) => boundedInt($v, 0, 100),
            'skor_sikap' => fn ($v) => boundedInt($v, 0, 40),
            'skor_pengetahuan' => fn ($v) => boundedInt($v, 0, 53),
            'skor_makan' => fn ($v) => boundedInt($v, 0, 18),
        ];
        $this->correct($actor, 'kuesioner', $id, $input, $allowed, $reason, $requestId);
    }

    public function correctRiskResult(
        ActorContext $actor,
        int $id,
        array $input,
        string $reason,
        string $requestId
    ): void {
        $probability = optionalDecimal($input['probabilitas_risiko'] ?? null, 0, 1);
        $category = enumValue(
            $input['kategori_risiko'] ?? null,
            ['rendah', 'sedang', 'tinggi']
        );
        $expectedCategory = $probability < 0.33
            ? 'rendah'
            : ($probability < 0.66 ? 'sedang' : 'tinggi');
        if ($category !== $expectedCategory) {
            throw new InvalidArgumentException(
                'Kategori risiko tidak konsisten dengan probabilitas.'
            );
        }
        $this->correct($actor, 'hasil_deteksi', $id, $input, [
            'probabilitas_risiko' => fn ($v) => optionalDecimal($v, 0, 1),
            'kategori_risiko' => fn ($v) => enumValue(
                $v, ['rendah', 'sedang', 'tinggi']
            ),
            'tanggal' => fn ($v) => optionalDate($v),
        ], $reason, $requestId);
    }

    public function correctHb(
        ActorContext $actor,
        int $id,
        mixed $value,
        mixed $category,
        mixed $date,
        string $reason,
        string $requestId
    ): void {
        $this->correct($actor, 'kadar_hb', $id, [
            'nilai_hb' => $value,
            'kategori_anemia' => $category,
            'tanggal_periksa' => $date,
        ], [
            'nilai_hb' => fn ($v) => optionalDecimal($v, 0, 30),
            'kategori_anemia' => fn ($v) => enumValue(
                $v, ['tidak_anemia', 'ringan', 'sedang', 'berat']
            ),
            'tanggal_periksa' => fn ($v) => optionalDate($v),
        ], $reason, $requestId);
    }

    public function correctTtd(
        ActorContext $actor,
        int $id,
        mixed $date,
        mixed $status,
        string $reason,
        string $requestId
    ): void {
        $this->correct($actor, 'konsumsi_ttd', $id, [
            'tanggal' => $date,
            'status_konsumsi' => $status,
        ], [
            'tanggal' => fn ($v) => optionalDate($v),
            'status_konsumsi' => fn ($v) => enumValue($v, ['sudah', 'belum']),
        ], $reason, $requestId);
    }

    public function correctMenstruation(
        ActorContext $actor,
        int $id,
        mixed $start,
        mixed $end,
        string $reason,
        string $requestId
    ): void {
        $start = optionalDate($start);
        $end = optionalDate($end);
        if ($start === null || ($end !== null && $end < $start)) {
            throw new InvalidArgumentException('Rentang menstruasi tidak valid.');
        }
        $this->assertActor($actor);
        $reason = $this->reason($reason);
        $this->pdo->beginTransaction();
        try {
            $record = $this->lockRecord('riwayat_haid', $id);
            if ($end === null) {
                $active = $this->pdo->prepare(
                    'SELECT COUNT(*) FROM riwayat_haid
                     WHERE user_id = ? AND id <> ? AND tanggal_selesai IS NULL
                       AND archived_at IS NULL'
                );
                $active->execute([$record['user_id'], $id]);
                if ((int) $active->fetchColumn() > 0) {
                    throw new DomainException('Siswa sudah memiliki siklus aktif.');
                }
            }
            $update = $this->pdo->prepare(
                'UPDATE riwayat_haid SET tanggal_mulai = ?, tanggal_selesai = ?,
                    corrected_at = CURRENT_TIMESTAMP, corrected_by = ?,
                    correction_reason = ? WHERE id = ?'
            );
            $update->execute([$start, $end, $actor->authenticatedActorId, $reason, $id]);
            $this->audit($actor, 'health.corrected', 'riwayat_haid', $id,
                $reason, $requestId, ['tanggal_mulai', 'tanggal_selesai']);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function archive(
        ActorContext $actor,
        string $table,
        int $id,
        string $reason,
        string $requestId
    ): void {
        $this->assertActor($actor);
        if (!in_array($table, self::TABLES, true)) {
            throw new InvalidArgumentException('Jenis data tidak valid.');
        }
        $reason = $this->reason($reason);
        $this->pdo->beginTransaction();
        try {
            $record = $this->lockRecord($table, $id);
            if ($record['archived_at'] !== null) {
                throw new DomainException('Data sudah diarsipkan.');
            }
            $update = $this->pdo->prepare(
                "UPDATE {$table} SET archived_at = CURRENT_TIMESTAMP,
                    archived_by = ?, archive_reason = ? WHERE id = ?"
            );
            $update->execute([$actor->authenticatedActorId, $reason, $id]);
            $this->audit($actor, 'health.archived', $table, $id,
                $reason, $requestId, []);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function correct(
        ActorContext $actor,
        string $table,
        int $id,
        array $input,
        array $validators,
        string $reason,
        string $requestId
    ): void {
        $this->assertActor($actor);
        $reason = $this->reason($reason);
        $values = [];
        foreach ($input as $field => $value) {
            if (!isset($validators[$field])) {
                throw new InvalidArgumentException('Field koreksi tidak valid.');
            }
            $values[$field] = $validators[$field]($value);
            if ($values[$field] === null) {
                throw new InvalidArgumentException('Nilai koreksi wajib.');
            }
        }
        if ($values === []) {
            throw new InvalidArgumentException('Tidak ada koreksi.');
        }
        $this->pdo->beginTransaction();
        try {
            $record = $this->lockRecord($table, $id);
            if ($record['archived_at'] !== null) {
                throw new DomainException('Data arsip tidak dapat dikoreksi.');
            }
            $assignments = [];
            $parameters = [];
            foreach ($values as $field => $value) {
                $assignments[] = "{$field} = ?";
                $parameters[] = $value;
            }
            $assignments[] = 'corrected_at = CURRENT_TIMESTAMP';
            $assignments[] = 'corrected_by = ?';
            $parameters[] = $actor->authenticatedActorId;
            $assignments[] = 'correction_reason = ?';
            $parameters[] = $reason;
            $parameters[] = $id;
            $update = $this->pdo->prepare(
                "UPDATE {$table} SET " . implode(', ', $assignments) . ' WHERE id = ?'
            );
            $update->execute($parameters);
            $this->audit($actor, 'health.corrected', $table, $id,
                $reason, $requestId, array_keys($values));
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function lockRecord(string $table, int $id): array
    {
        if ($id < 1 || !in_array($table, self::TABLES, true)) {
            throw new InvalidArgumentException('Target tidak valid.');
        }
        $sql = "SELECT id, user_id, archived_at FROM {$table} WHERE id = ?";
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$id]);
        $record = $statement->fetch();
        if (!$record) {
            throw new DomainException('Data kesehatan tidak ditemukan.');
        }
        return $record;
    }

    private function assertActor(ActorContext $actor): void
    {
        if (!SuperadminGuard::contextIsAuthorized($actor)
            || !actionAllowedForActor($actor, 'manage_health_records')) {
            throw new DomainException('Aksi master kesehatan ditolak.');
        }
    }

    private function reason(string $reason): string
    {
        if (!in_array($reason, self::REASONS, true)) {
            throw new InvalidArgumentException('Alasan wajib dipilih.');
        }
        return $reason;
    }

    private function audit(
        ActorContext $actor,
        string $action,
        string $table,
        int $id,
        string $reason,
        string $requestId,
        array $fields
    ): void {
        (new ImpersonationMutationAudit($this->pdo))->record(
            $actor,
            $action,
            $table,
            $id,
            'success',
            '/superadmin/health',
            $requestId,
            ['reason_category' => $reason, 'changed_fields' => $fields]
        );
    }
}
