<?php

declare(strict_types=1);

require_once __DIR__ . '/SuperadminOperationalService.php';
require_once dirname(__DIR__, 2) . '/config/validation.php';

final class SuperadminNotificationService extends SuperadminOperationalService
{
    public function correctSchedule(
        ActorContext $actor,
        int $id,
        mixed $time,
        mixed $day,
        mixed $active,
        string $reason,
        string $requestId
    ): void {
        $this->assertActor($actor);
        $time = normalizeText($time, 8);
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time)) {
            throw new InvalidArgumentException('Jam pengingat tidak valid.');
        }
        if (strlen($time) === 5) {
            $time .= ':00';
        }
        $day = enumValue($day, ['harian', 'mingguan', 'saat_menstruasi']);
        $active = filter_var($active, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($active === null) {
            throw new InvalidArgumentException('Status jadwal tidak valid.');
        }
        $this->correctRecord($actor, 'jadwal_notifikasi', $id, [
            'jam_pengingat' => $time,
            'hari' => $day,
            'aktif' => $active ? 1 : 0,
        ], $reason, $requestId);
    }

    public function correctDeliveryConfirmation(
        ActorContext $actor,
        int $id,
        mixed $confirmed,
        string $reason,
        string $requestId
    ): void {
        $this->assertActor($actor);
        $confirmed = filter_var(
            $confirmed, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE
        );
        if ($confirmed === null) {
            throw new InvalidArgumentException('Konfirmasi tidak valid.');
        }
        $this->correctRecord($actor, 'log_notifikasi', $id, [
            'sudah_dikonfirmasi' => $confirmed ? 1 : 0,
        ], $reason, $requestId);
    }

    public function archive(
        ActorContext $actor,
        string $table,
        int $id,
        string $reason,
        string $requestId
    ): void {
        if (!in_array($table, ['jadwal_notifikasi', 'log_notifikasi'], true)) {
            throw new InvalidArgumentException('Jenis notifikasi tidak valid.');
        }
        $this->archiveRecord($actor, $table, $id, $reason, $requestId);
    }
}
