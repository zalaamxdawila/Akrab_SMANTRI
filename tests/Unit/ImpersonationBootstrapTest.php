<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ImpersonationBootstrapTest extends TestCase
{
    public function testEveryImpersonatedPostGetsExpiryAndAuditHooks(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/config.php');

        self::assertStringContainsString('expireIfNeeded()', $contents);
        self::assertStringContainsString('ActorContextResolver', $contents);
        self::assertStringContainsString('registerCurrentMutation', $contents);
        self::assertStringContainsString(
            "\$_SERVER['REQUEST_METHOD'] ?? 'GET'",
            $contents
        );

        $audit = file_get_contents(
            dirname(__DIR__, 2)
            . '/app/Security/ImpersonationMutationAudit.php'
        );
        self::assertStringContainsString('http.mutation_started', $audit);
    }
}
