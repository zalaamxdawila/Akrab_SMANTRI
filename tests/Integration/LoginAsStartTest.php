<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Security/ImpersonationService.php';

final class LoginAsStartTest extends TestCase
{
    public function testTargetPickerReturnsOnlyActiveApplicationRoles(): void
    {
        $pdo = $this->database();
        $session = ['user_id' => 1, 'role' => 'superadmin'];
        $result = (new ImpersonationService($pdo, $session, static fn () => null))
            ->paginateTargets('', 1);
        self::assertSame([2], array_column($result['items'], 'id'));
    }

    public function testFiveFailedStepUpsTriggerSessionRateLimitWithoutPasswordAudit(): void
    {
        putenv('AKRAB_SUPERADMIN_ENABLED=true');
        $pdo = $this->database();
        $session = ['user_id' => 1, 'role' => 'superadmin'];
        $service = new ImpersonationService($pdo, $session, static fn () => null);
        for ($i = 0; $i < 5; $i++) {
            try {
                $service->start(1, 'wrong-secret', 2, 'support', 'Valid reason', 1000 + $i);
            } catch (DomainException) {
            }
        }
        try {
            $service->start(1, 'correct-password', 2, 'support', 'Valid reason', 1006);
            self::fail('Rate limit should block the sixth attempt.');
        } catch (DomainException $exception) {
            self::assertStringContainsString('locked', $exception->getMessage());
            self::assertSame(0, (int) $pdo->query('SELECT COUNT(*) FROM audit_log')->fetchColumn());
        } finally {
            putenv('AKRAB_SUPERADMIN_ENABLED');
        }
    }

    private function database(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('CREATE TABLE users (
            id INTEGER PRIMARY KEY, nama TEXT, username TEXT, role TEXT,
            status TEXT, password_hash TEXT
        )');
        $pdo->exec('CREATE TABLE impersonation_sessions (
            id INTEGER PRIMARY KEY AUTOINCREMENT, superadmin_id INTEGER,
            target_user_id INTEGER, reason_category TEXT, reason_note TEXT,
            started_at TEXT, expires_at TEXT, ended_at TEXT, status TEXT
        )');
        $pdo->exec('CREATE TABLE audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT, actor_id INTEGER,
            authenticated_actor_id INTEGER, effective_actor_id INTEGER,
            impersonation_session_id INTEGER, request_id TEXT, action TEXT,
            target_type TEXT, target_id INTEGER, metadata_json TEXT
        )');
        $insert = $pdo->prepare('INSERT INTO users VALUES (?, ?, ?, ?, ?, ?)');
        $insert->execute([1, 'Master', 'master', 'superadmin', 'active',
            password_hash('correct-password', PASSWORD_DEFAULT)]);
        $insert->execute([2, 'Siswa Aktif', 'siswa', 'siswa', 'active', 'x']);
        $insert->execute([3, 'UKS Nonaktif', 'uks', 'uks', 'inactive', 'x']);
        return $pdo;
    }
}
