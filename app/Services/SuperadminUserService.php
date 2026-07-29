<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Security/SuperadminGuard.php';
require_once dirname(__DIR__) . '/Security/ImpersonationMutationAudit.php';

final class SuperadminUserService
{
    private const ROLES = ['siswa', 'uks', 'orangtua'];
    private const STATUSES = ['active', 'inactive', 'archived'];
    private const REASONS = ['correction', 'verification', 'support', 'data_governance'];

    public function __construct(private PDO $pdo)
    {
    }

    public function create(ActorContext $actor, array $input, string $requestId): int
    {
        $this->assertActor($actor, 'manage_users');
        $nama = trim((string) ($input['nama'] ?? ''));
        $username = strtolower(trim((string) ($input['username'] ?? '')));
        $role = (string) ($input['role'] ?? '');
        $password = (string) ($input['password'] ?? '');
        $kelas = trim((string) ($input['kelas'] ?? ''));
        if (mb_strlen($nama) < 2 || mb_strlen($nama) > 100) {
            throw new InvalidArgumentException('Nama harus 2–100 karakter.');
        }
        if (!preg_match('/^[a-z0-9][a-z0-9._-]{2,49}$/', $username)) {
            throw new InvalidArgumentException('Username tidak valid.');
        }
        if (!in_array($role, self::ROLES, true)) {
            throw new InvalidArgumentException('Role tidak valid.');
        }
        if (strlen($password) < 12 || strlen($password) > 1024) {
            throw new InvalidArgumentException('Password awal minimal 12 karakter.');
        }
        if (mb_strlen($kelas) > 20) {
            throw new InvalidArgumentException('Kelas terlalu panjang.');
        }
        if ($role !== 'siswa') {
            $kelas = '';
        }

        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO users (nama, role, username, password_hash, status, kelas)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $statement->execute([
                $nama,
                $role,
                $username,
                password_hash($password, PASSWORD_DEFAULT),
                'active',
                $kelas === '' ? null : $kelas,
            ]);
            $id = (int) $this->pdo->lastInsertId();
            $this->audit($actor, 'user.created', $id, 'created', 'support', $requestId);
            $this->pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function correct(
        ActorContext $actor,
        int $userId,
        array $input,
        string $reason,
        string $requestId
    ): void {
        $this->assertActor($actor, 'manage_users');
        $reason = $this->reason($reason);
        $user = $this->lockUser($userId);
        try {
            $this->assertMutable($actor, $user);
            if (isset($input['role']) && $input['role'] !== $user['role']) {
                throw new DomainException('Konversi role akun tidak diizinkan.');
            }
            $nama = trim((string) ($input['nama'] ?? $user['nama']));
            $kelas = trim((string) ($input['kelas'] ?? ($user['kelas'] ?? '')));
            if (mb_strlen($nama) < 2 || mb_strlen($nama) > 100
                || mb_strlen($kelas) > 20) {
                throw new InvalidArgumentException('Koreksi tidak valid.');
            }
            if ($user['role'] !== 'siswa') {
                $kelas = '';
            }
            $update = $this->pdo->prepare(
                'UPDATE users SET nama = ?, kelas = ? WHERE id = ?'
            );
            $update->execute([$nama, $kelas === '' ? null : $kelas, $userId]);
            $this->audit($actor, 'user.corrected', $userId, 'updated', $reason, $requestId);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function changeStatus(
        ActorContext $actor,
        int $userId,
        string $status,
        string $reason,
        string $requestId
    ): void {
        $this->assertActor($actor, 'manage_users');
        if (!in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException('Status tidak valid.');
        }
        $reason = $this->reason($reason);
        $user = $this->lockUser($userId);
        $this->assertMutable($actor, $user);
        try {
            $update = $this->pdo->prepare(
                'UPDATE users SET status = ?, status_changed_at = CURRENT_TIMESTAMP,
                    status_changed_by = ?, status_reason = ? WHERE id = ?'
            );
            $update->execute([$status, $actor->authenticatedActorId, $reason, $userId]);
            $this->audit($actor, 'user.status_changed', $userId, $status, $reason, $requestId);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function lockUser(int $userId): array
    {
        if ($userId < 1) {
            throw new InvalidArgumentException('Target tidak valid.');
        }
        $this->pdo->beginTransaction();
        $sql = 'SELECT id, nama, role, kelas FROM users WHERE id = ?';
        if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $sql .= ' FOR UPDATE';
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute([$userId]);
        $user = $statement->fetch();
        if (!$user) {
            $this->pdo->rollBack();
            throw new DomainException('Pengguna tidak ditemukan.');
        }
        return $user;
    }

    private function assertActor(ActorContext $actor, string $action): void
    {
        if (!SuperadminGuard::contextIsAuthorized($actor)
            || !actionAllowedForActor($actor, $action)) {
            throw new DomainException('Aksi superadmin ditolak.');
        }
    }

    private function assertMutable(ActorContext $actor, array $user): void
    {
        if ($user['role'] === 'superadmin'
            || (int) $user['id'] === $actor->authenticatedActorId) {
            throw new DomainException('Akun superadmin tidak dapat diubah.');
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
        int $targetId,
        string $outcome,
        string $reason,
        string $requestId
    ): void {
        (new ImpersonationMutationAudit($this->pdo))->record(
            $actor,
            $action,
            'user',
            $targetId,
            $outcome,
            '/superadmin',
            $requestId,
            ['reason_category' => $reason]
        );
    }
}
