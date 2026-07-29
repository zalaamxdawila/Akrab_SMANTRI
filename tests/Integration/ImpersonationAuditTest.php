<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Security/ActorContext.php';
require_once dirname(__DIR__, 2) . '/app/Security/ImpersonationMutationAudit.php';

final class ImpersonationAuditTest extends TestCase
{
    public function testMutationAuditStoresTwoActorsAndRedactsMetadata(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec(
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
        $context = new ActorContext(
            1,
            2,
            'superadmin',
            'siswa',
            10,
            'support'
        );

        (new ImpersonationMutationAudit($pdo))->record(
            $context,
            'consultation.reply',
            'consultation',
            99,
            'success',
            '/siswa/konsultasi.php',
            'request-123',
            ['password' => 'secret', 'note' => 'PII']
        );

        $row = $pdo->query('SELECT * FROM audit_log')->fetch();
        self::assertSame(1, (int) $row['authenticated_actor_id']);
        self::assertSame(2, (int) $row['effective_actor_id']);
        self::assertSame(10, (int) $row['impersonation_session_id']);
        self::assertSame('request-123', $row['request_id']);
        self::assertSame(
            [
                'outcome' => 'success',
                'route' => '/siswa/konsultasi.php',
                'reason_category' => 'support',
            ],
            json_decode($row['metadata_json'], true, 512, JSON_THROW_ON_ERROR)
        );

        foreach (['failed', 'forbidden'] as $outcome) {
            (new ImpersonationMutationAudit($pdo))->record(
                $context,
                'http.mutation',
                'http_request',
                null,
                $outcome,
                '/siswa/konsultasi.php',
                'request-' . $outcome
            );
        }
        $outcomes = $pdo->query(
            'SELECT metadata_json FROM audit_log ORDER BY id DESC LIMIT 2'
        )->fetchAll(PDO::FETCH_COLUMN);
        self::assertStringContainsString('forbidden', $outcomes[0]);
        self::assertStringContainsString('failed', $outcomes[1]);
    }
}
