<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FrontendSecurityTest extends TestCase
{
    public function testServiceWorkerDoesNotCachePrivateNavigation(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/service-worker.js');
        self::assertStringContainsString("request.mode === 'navigate'", $contents);
        self::assertStringContainsString("fetch(request).catch", $contents);
        self::assertStringContainsString('caches.delete', $contents);
        self::assertStringNotContainsString("'/login.php'", $contents);
    }

    public function testSecurityHeadersAreConfigured(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/.htaccess');
        foreach (['Content-Security-Policy', 'Strict-Transport-Security', 'X-Content-Type-Options', 'X-Frame-Options', 'Permissions-Policy'] as $header) {
            self::assertStringContainsString($header, $contents);
        }
    }

    public function testAssistantHasDisclaimerAndAccessibleDialog(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/assets/js/chatbot.js');
        self::assertStringContainsString('Asisten Informasi AKRAB', $contents);
        self::assertStringContainsString('bukan dokter', $contents);
        self::assertStringContainsString('role="dialog"', $contents);
        self::assertStringContainsString('aria-live="polite"', $contents);
        self::assertStringNotContainsString('Dokter AI', $contents);
    }

    public function testDynamicTimestampCacheBustingWasRemoved(): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2), FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                self::assertStringNotContainsString('?v=<?= time() ?>', file_get_contents($file->getPathname()), $file->getPathname());
            }
        }
    }
}
