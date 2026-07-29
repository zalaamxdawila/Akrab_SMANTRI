<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Services/SuperadminProvisioningService.php';

final class SuperadminProvisioningTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('SQLite PDO driver is required for isolated integration tests.');
        }

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec(
            "CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nama TEXT NOT NULL,
                role TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL
            )"
        );
        $this->pdo->exec(
            'CREATE TABLE audit_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                actor_id INTEGER NULL,
                action TEXT NOT NULL,
                target_type TEXT NOT NULL,
                target_id INTEGER NULL,
                metadata_json TEXT NULL
            )'
        );
    }

    public function testProvisionAndRecoveryAreIdempotent(): void
    {
        $service = new SuperadminProvisioningService($this->pdo);
        $firstId = $service->provision('Master AKRAB', 'akrab_master', 'StrongPassword!2026');
        $secondId = $service->provision('Master Baru', 'akrab_master', 'ChangedPassword!2026');

        self::assertSame($firstId, $secondId);
        self::assertSame(1, (int) $this->pdo->query(
            "SELECT COUNT(*) FROM users WHERE role = 'superadmin'"
        )->fetchColumn());
        $user = $this->pdo->query(
            "SELECT nama, status, password_hash FROM users WHERE role = 'superadmin'"
        )->fetch();
        self::assertSame('Master Baru', $user['nama']);
        self::assertSame('active', $user['status']);
        self::assertTrue(password_verify('ChangedPassword!2026', $user['password_hash']));
        self::assertSame(2, (int) $this->pdo->query(
            'SELECT COUNT(*) FROM audit_log'
        )->fetchColumn());
        $metadata = (string) $this->pdo->query(
            'SELECT metadata_json FROM audit_log ORDER BY id DESC LIMIT 1'
        )->fetchColumn();
        self::assertStringNotContainsString('ChangedPassword!2026', $metadata);
        self::assertSame(
            ['operator' => 'cli', 'mode' => 'recovery'],
            json_decode($metadata, true, 512, JSON_THROW_ON_ERROR)
        );
    }

    public function testDifferentSecondSuperadminIsRejected(): void
    {
        $service = new SuperadminProvisioningService($this->pdo);
        $service->provision('Master AKRAB', 'akrab_master', 'StrongPassword!2026');

        $this->expectException(DomainException::class);
        $service->provision('Master Lain', 'other_master', 'AnotherPassword!2026');
    }

    public function testInvalidInputDoesNotCreateAccountOrAudit(): void
    {
        $service = new SuperadminProvisioningService($this->pdo);

        try {
            $service->provision('Master AKRAB', 'invalid username', 'short');
            self::fail('Invalid provisioning input should be rejected.');
        } catch (InvalidArgumentException) {
            self::assertSame(0, (int) $this->pdo->query(
                'SELECT COUNT(*) FROM users'
            )->fetchColumn());
            self::assertSame(0, (int) $this->pdo->query(
                'SELECT COUNT(*) FROM audit_log'
            )->fetchColumn());
        }
    }
}
