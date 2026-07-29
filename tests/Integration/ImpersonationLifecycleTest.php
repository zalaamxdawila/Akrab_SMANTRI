<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Security/ImpersonationService.php';

final class ImpersonationLifecycleTest extends TestCase
{
    private PDO $pdo;
    private array $session;
    private int $regenerations;
    private string $previousTimezone;

    protected function setUp(): void
    {
        putenv('AKRAB_SUPERADMIN_ENABLED=true');
        $this->previousTimezone = date_default_timezone_get();
        date_default_timezone_set('Asia/Jakarta');
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                nama TEXT NOT NULL,
                role TEXT NOT NULL,
                status TEXT NOT NULL,
                password_hash TEXT NOT NULL
            )'
        );
        $this->pdo->exec(
            'CREATE TABLE impersonation_sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                superadmin_id INTEGER NOT NULL,
                target_user_id INTEGER NOT NULL,
                reason_category TEXT NOT NULL,
                reason_note TEXT NOT NULL,
                started_at TEXT NOT NULL,
                expires_at TEXT NOT NULL,
                ended_at TEXT NULL,
                status TEXT NOT NULL
            )'
        );
        $this->pdo->exec(
            'CREATE TABLE audit_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                actor_id INTEGER NULL,
                authenticated_actor_id INTEGER NULL,
                effective_actor_id INTEGER NULL,
                impersonation_session_id INTEGER NULL,
                request_id TEXT NULL,
                action TEXT NOT NULL,
                target_type TEXT NOT NULL,
                target_id INTEGER NULL,
                metadata_json TEXT NULL
            )'
        );
        $insert = $this->pdo->prepare(
            'INSERT INTO users VALUES (?, ?, ?, ?, ?)'
        );
        $insert->execute([1, 'Master', 'superadmin', 'active', password_hash('MasterPassword!2026', PASSWORD_DEFAULT)]);
        $insert->execute([2, 'Siswa', 'siswa', 'active', password_hash('StudentPassword!2026', PASSWORD_DEFAULT)]);
        $insert->execute([3, 'UKS Nonaktif', 'uks', 'inactive', password_hash('InactivePassword!2026', PASSWORD_DEFAULT)]);
        $this->session = ['user_id' => 1, 'role' => 'superadmin', 'nama' => 'Master'];
        $this->regenerations = 0;
    }

    protected function tearDown(): void
    {
        putenv('AKRAB_SUPERADMIN_ENABLED');
        date_default_timezone_set($this->previousTimezone);
    }

    public function testStartAndEndPreserveAuthenticatedSuperadmin(): void
    {
        $service = $this->service();
        $id = $service->start(
            1,
            'MasterPassword!2026',
            2,
            'support',
            'Memeriksa kendala siswa',
            1_000
        );

        self::assertSame($id, $this->session['_impersonation_session_id']);
        self::assertSame(2, $this->session['user_id']);
        self::assertSame('siswa', $this->session['role']);
        self::assertSame(1, $this->regenerations);
        self::assertSame(
            '1970-01-01 00:31:40',
            $this->pdo->query(
                "SELECT expires_at FROM impersonation_sessions WHERE id = {$id}"
            )->fetchColumn()
        );

        $service->end(1_100);

        self::assertSame(1, $this->session['user_id']);
        self::assertSame('superadmin', $this->session['role']);
        self::assertArrayNotHasKey('_impersonation_session_id', $this->session);
        self::assertSame(2, $this->regenerations);
        self::assertSame('ended', $this->pdo->query(
            "SELECT status FROM impersonation_sessions WHERE id = {$id}"
        )->fetchColumn());
    }

    public function testStartRequiresStepUpAndValidActiveTarget(): void
    {
        $service = $this->service();

        $this->expectException(DomainException::class);
        $service->start(1, 'wrong-password', 3, 'support', 'Valid note', 1_000);
    }

    public function testInactiveTargetAndInvalidReasonAreRejected(): void
    {
        $service = $this->service();

        try {
            $service->start(
                1,
                'MasterPassword!2026',
                3,
                'support',
                'Valid note',
                1_000
            );
            self::fail('Inactive target should be rejected.');
        } catch (DomainException) {
            self::assertArrayNotHasKey(
                '_impersonation_session_id',
                $this->session
            );
        }

        $this->expectException(InvalidArgumentException::class);
        $service->start(
            1,
            'MasterPassword!2026',
            2,
            'free-form-category',
            'Valid note',
            1_000
        );
    }

    public function testFeatureFlagOffBlocksStart(): void
    {
        putenv('AKRAB_SUPERADMIN_ENABLED=false');
        $this->expectException(DomainException::class);

        $this->service()->start(
            1,
            'MasterPassword!2026',
            2,
            'support',
            'Valid note',
            1_000
        );
    }

    public function testNestedSessionIsRejectedAndExpiryCannotBeRevived(): void
    {
        $service = $this->service();
        $id = $service->start(
            1,
            'MasterPassword!2026',
            2,
            'support',
            'Memeriksa kendala siswa',
            1_000
        );

        try {
            $service->start(
                1,
                'MasterPassword!2026',
                2,
                'support',
                'Percobaan nested',
                1_001
            );
            self::fail('Nested impersonation must be rejected.');
        } catch (DomainException) {
            self::assertTrue($service->expireIfNeeded(1_901));
            self::assertSame('expired', $this->pdo->query(
                "SELECT status FROM impersonation_sessions WHERE id = {$id}"
            )->fetchColumn());
            self::assertSame(1, $this->session['user_id']);
            self::assertFalse($service->expireIfNeeded(1_902));
        }
    }

    private function service(): ImpersonationService
    {
        return new ImpersonationService(
            $this->pdo,
            $this->session,
            function (): void {
                $this->regenerations++;
            }
        );
    }
}
