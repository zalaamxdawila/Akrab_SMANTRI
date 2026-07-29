<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ImpersonationBannerTest extends TestCase
{
    public function testBannerAndReturnEndpointAreAccessibleAndPostOnly(): void
    {
        $banner = file_get_contents(
            dirname(__DIR__, 2) . '/views/partials/impersonation_banner.php'
        );
        self::assertStringContainsString('role="status"', $banner);
        self::assertStringContainsString('csrfInput()', $banner);
        self::assertStringContainsString('data-impersonation-countdown', $banner);
        $endpoint = file_get_contents(dirname(__DIR__, 2) . '/end_impersonation.php');
        self::assertStringContainsString("REQUEST_METHOD", $endpoint);
        self::assertStringContainsString("!== 'POST'", $endpoint);
        self::assertStringContainsString('->end()', $endpoint);
    }
}
