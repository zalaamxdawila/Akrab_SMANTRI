<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2)
    . '/app/Repositories/SuperadminAuditRepository.php';

final class SuperadminAuditReadTest extends TestCase
{
    public function testAuditFiltersDualActorsOutcomeAndRequestId(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                nama TEXT,
                role TEXT
            )'
        );
        $pdo->exec(
            'CREATE TABLE audit_log (
                id INTEGER PRIMARY KEY,
                actor_id INTEGER,
                authenticated_actor_id INTEGER,
                effective_actor_id INTEGER,
                impersonation_session_id INTEGER,
                request_id TEXT,
                action TEXT,
                target_type TEXT,
                target_id INTEGER,
                metadata_json TEXT,
                created_at TEXT
            )'
        );
        $pdo->exec(
            "INSERT INTO users VALUES
                (1, 'Master', 'superadmin'),
                (2, 'Siti', 'siswa')"
        );
        $insert = $pdo->prepare(
            'INSERT INTO audit_log VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([
            1, 1, 1, 2, 10, 'req-1', 'http.mutation',
            'consultation', 9,
            '{"outcome":"success","route":"/siswa/konsultasi.php"}',
            '2026-07-29 10:00:00',
        ]);
        $insert->execute([
            2, 1, 1, 2, 10, 'req-2', 'http.mutation',
            'consultation', 10,
            '{"outcome":"failed","route":"/siswa/konsultasi.php"}',
            '2026-07-29 11:00:00',
        ]);

        $result = (new SuperadminAuditRepository($pdo))->paginate(
            [
                'authenticated_actor_id' => 1,
                'effective_actor_id' => 2,
                'action' => 'http.mutation',
                'outcome' => 'failed',
                'request_id' => 'req-2',
                'date_from' => '2026-07-29',
                'date_to' => '2026-07-29',
            ],
            1,
            25
        );

        self::assertSame(1, $result['total']);
        self::assertSame('Master', $result['items'][0]['authenticated_name']);
        self::assertSame('Siti', $result['items'][0]['effective_name']);
        self::assertSame('failed', $result['items'][0]['outcome']);
        self::assertSame('/siswa/konsultasi.php', $result['items'][0]['route']);
        self::assertArrayNotHasKey('metadata_json', $result['items'][0]);
    }
}
