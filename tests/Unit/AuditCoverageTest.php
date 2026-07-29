<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AuditCoverageTest extends TestCase
{
    public function testSensitiveActionsEmitAuditEvents(): void
    {
        $root = dirname(__DIR__, 2);
        $expectations = [
            'login.php' => 'auth.login_succeeded',
            'uks/export_csv.php' => 'data.exported',
            'uks/detail_siswa.php' => 'health_record.viewed',
            'uks/kelola_artikel.php' => 'article.created',
            'uks/kelola_tautan.php' => 'parent_link.',
        ];
        foreach ($expectations as $path => $event) {
            self::assertStringContainsString($event, file_get_contents($root . '/' . $path), $path);
        }
    }
}
