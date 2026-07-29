<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Security/ActorContext.php';
require_once dirname(__DIR__, 2) . '/app/Security/ActorContextResolver.php';

final class ActorContextTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        putenv('AKRAB_SUPERADMIN_ENABLED=true');
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                nama TEXT NOT NULL,
                role TEXT NOT NULL,
                status TEXT NOT NULL
            )'
        );
        $this->pdo->exec(
            'CREATE TABLE impersonation_sessions (
                id INTEGER PRIMARY KEY,
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
            "INSERT INTO users VALUES
                (1, 'Master', 'superadmin', 'active'),
                (2, 'Siswa', 'siswa', 'active'),
                (3, 'Palsu', 'unknown', 'active')"
        );
    }

    protected function tearDown(): void
    {
        putenv('AKRAB_SUPERADMIN_ENABLED');
    }

    public function testNormalContextComesFromDatabaseNotForgedSessionRole(): void
    {
        $context = (new ActorContextResolver($this->pdo))->resolve(
            ['user_id' => 2, 'role' => 'superadmin'],
            1_000
        );

        self::assertSame(2, $context->authenticatedActorId);
        self::assertSame(2, $context->effectiveActorId);
        self::assertSame('siswa', $context->effectiveRole);
        self::assertFalse($context->isImpersonating());
    }

    public function testActiveImpersonationKeepsTwoImmutableActors(): void
    {
        $this->pdo->exec(
            "INSERT INTO impersonation_sessions VALUES
                (10, 1, 2, 'support', 'Verifikasi alur', '1970-01-01 00:10:00',
                 '1970-01-01 00:20:00', NULL, 'active')"
        );

        $context = (new ActorContextResolver($this->pdo))->resolve(
            [
                'user_id' => 2,
                'role' => 'superadmin',
                '_impersonation_session_id' => 10,
            ],
            1_000
        );

        self::assertSame(1, $context->authenticatedActorId);
        self::assertSame(2, $context->effectiveActorId);
        self::assertSame('siswa', $context->effectiveRole);
        self::assertSame(10, $context->impersonationSessionId);
        self::assertSame('support', $context->impersonationReasonCategory);
    }

    public function testUnknownOrInactiveDatabaseRoleIsRejected(): void
    {
        $this->expectException(DomainException::class);

        (new ActorContextResolver($this->pdo))->resolve(
            ['user_id' => 3, 'role' => 'siswa'],
            1_000
        );
    }

    public function testInvalidImpersonationMarkerIsRejected(): void
    {
        $this->expectException(DomainException::class);

        (new ActorContextResolver($this->pdo))->resolve(
            ['user_id' => 2, '_impersonation_session_id' => 'forged'],
            1_000
        );
    }

    public function testSuperadminSessionIsRejectedWhenFeatureTurnsOff(): void
    {
        putenv('AKRAB_SUPERADMIN_ENABLED=false');
        $this->expectException(DomainException::class);

        (new ActorContextResolver($this->pdo))->resolve(
            ['user_id' => 1],
            1_000
        );
    }
}
