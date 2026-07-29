<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ImpersonationMigrationTest extends TestCase
{
    public function testMigrationDefinesSessionAndDualActorAuditSchema(): void
    {
        $path = dirname(__DIR__, 2) . '/database/migrations/009_impersonation_audit.php';

        self::assertFileExists($path);
        $contents = file_get_contents($path);
        foreach ([
            'impersonation_sessions',
            'superadmin_id',
            'target_user_id',
            'reason_category',
            'expires_at',
            'authenticated_actor_id',
            'effective_actor_id',
            'impersonation_session_id',
            'request_id',
        ] as $needle) {
            self::assertStringContainsString($needle, $contents);
        }
        self::assertStringNotContainsString('DROP TABLE', strtoupper($contents));
    }
}
