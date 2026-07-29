<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ObservabilityTest extends TestCase
{
    public function testLogContextUsesAllowlistAndDropsSecrets(): void
    {
        $safe = safeLogContext(['route' => '/login', 'outcome' => 'failed', 'password' => 'secret', 'username' => 'student']);
        self::assertSame(['outcome' => 'failed', 'route' => '/login'], $safe);
        self::assertArrayNotHasKey('password', $safe);
        self::assertArrayNotHasKey('username', $safe);
    }

    public function testHealthResponseDoesNotExposeInternalConfiguration(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/health.php');
        self::assertStringContainsString("['status' => \$status]", $contents);
        self::assertStringNotContainsString("'error' =>", $contents);
        self::assertStringContainsString("Cache-Control: no-store", $contents);
    }
}
