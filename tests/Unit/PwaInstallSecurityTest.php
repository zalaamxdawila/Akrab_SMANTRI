<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PwaInstallSecurityTest extends TestCase
{
    public function testManifestDescribesAnInstallableRootScopedApplication(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root . '/manifest.json'), true);

        self::assertIsArray($manifest);
        self::assertSame('/', $manifest['id'] ?? null);
        self::assertSame('/', $manifest['start_url'] ?? null);
        self::assertSame('/', $manifest['scope'] ?? null);
        self::assertSame('standalone', $manifest['display'] ?? null);
        self::assertSame('id', $manifest['lang'] ?? null);
        self::assertFalse($manifest['prefer_related_applications'] ?? null);

        $icons = [];
        foreach ($manifest['icons'] ?? [] as $icon) {
            $icons[(string) ($icon['sizes'] ?? '')] = $icon;
        }

        foreach ([192, 512] as $size) {
            $key = $size . 'x' . $size;
            self::assertArrayHasKey($key, $icons);
            self::assertSame('image/png', $icons[$key]['type'] ?? null);

            $path = $root . (string) ($icons[$key]['src'] ?? '');
            self::assertFileExists($path);
            $dimensions = getimagesize($path);
            self::assertIsArray($dimensions);
            self::assertSame($size, $dimensions[0]);
            self::assertSame($size, $dimensions[1]);
        }
    }

    public function testLandingPageProvidesAccessibleDirectInstallControls(): void
    {
        $root = dirname(__DIR__, 2);
        $landing = (string) file_get_contents($root . '/index.php');
        $script = (string) file_get_contents($root . '/assets/js/app-init.js');

        self::assertStringContainsString('rel="manifest"', $landing);
        self::assertStringContainsString('rel="apple-touch-icon"', $landing);
        self::assertStringContainsString('name="theme-color"', $landing);
        self::assertStringContainsString('data-install-app', $landing);
        self::assertStringContainsString('data-install-status', $landing);
        self::assertStringContainsString('aria-live="polite"', $landing);
        self::assertStringContainsString('data-install-help', $landing);
        self::assertStringContainsString('beforeinstallprompt', $script);
        self::assertStringContainsString('appinstalled', $script);
        self::assertStringContainsString("matchMedia('(display-mode: standalone)')", $script);
        self::assertStringNotContainsString('beforeinstallprompt', $landing);
    }

    public function testServiceWorkerCachesOnlySafeSameOriginStaticResources(): void
    {
        $root = dirname(__DIR__, 2);
        $worker = (string) file_get_contents($root . '/service-worker.js');

        self::assertStringContainsString("request.method !== 'GET'", $worker);
        self::assertStringContainsString("request.mode === 'navigate'", $worker);
        self::assertStringContainsString('url.origin !== self.location.origin', $worker);
        self::assertStringContainsString('SAFE_STATIC_PREFIXES', $worker);
        self::assertStringContainsString('SAFE_DESTINATIONS', $worker);
        self::assertStringContainsString("response.headers.get('Cache-Control')", $worker);
        self::assertStringContainsString("APP_CACHE_PREFIX = 'akrab-static-'", $worker);
        self::assertStringContainsString('key.startsWith(APP_CACHE_PREFIX)', $worker);
        self::assertStringNotContainsString('keys.filter(key => key !== CACHE_NAME)', $worker);
        self::assertStringNotContainsString("'/login.php'", $worker);
        self::assertStringNotContainsString("'/siswa/", $worker);
        self::assertStringNotContainsString("'/uks/", $worker);
        self::assertStringNotContainsString("'/superadmin/", $worker);
    }

    public function testStaticAssetsPreferTheNetworkAndReleaseVersionsStayAligned(): void
    {
        $root = dirname(__DIR__, 2);
        $worker = (string) file_get_contents($root . '/service-worker.js');
        $controller = (string) file_get_contents($root . '/assets/js/app-init.js');
        $landing = (string) file_get_contents($root . '/index.php');

        self::assertStringContainsString('fetch(request)', $worker);
        self::assertStringContainsString('return cached', $worker);
        self::assertLessThan(
            strpos($worker, 'caches.match(request)'),
            strpos($worker, 'fetch(request)'),
            'Static assets must try the network before falling back to cache.'
        );
        self::assertStringContainsString('20260831-mobile-header-v1', $worker);
        self::assertStringContainsString('service-worker.js?v=20260831-mobile-header-v1', $controller);
        self::assertStringContainsString('style.css?v=20260831-mobile-header-v1', $landing);
        self::assertStringContainsString('app-init.js?v=20260831-safe-install', $landing);
    }

    public function testServiceWorkerUpdateCannotForceReloadAnInProgressForm(): void
    {
        $script = (string) file_get_contents(dirname(__DIR__, 2) . '/assets/js/app-init.js');

        self::assertStringContainsString(
            "register('/service-worker.js?v=20260831-mobile-header-v1'",
            $script
        );
        self::assertStringNotContainsString('controllerchange', $script);
        self::assertStringNotContainsString('window.location.reload()', $script);
    }

    public function testEveryPwaPageLoadsTheCurrentInstallController(): void
    {
        $root = dirname(__DIR__, 2);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );
        $matchedPages = 0;

        foreach ($iterator as $file) {
            $path = $file->getPathname();
            if (
                $file->getExtension() !== 'php'
                || str_contains($path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR)
                || str_contains($path, DIRECTORY_SEPARATOR . '.deploy-backup' . DIRECTORY_SEPARATOR)
            ) {
                continue;
            }

            $contents = (string) file_get_contents($path);
            if (!str_contains($contents, 'app-init.js')) {
                continue;
            }

            $matchedPages++;
            self::assertStringContainsString(
                'app-init.js?v=20260831-safe-install',
                $contents,
                $path
            );
        }

        self::assertGreaterThan(0, $matchedPages);
    }

    public function testPwaMetadataUsesExplicitSafeCachingHeaders(): void
    {
        $htaccess = (string) file_get_contents(dirname(__DIR__, 2) . '/.htaccess');

        self::assertStringContainsString('<Files "service-worker.js">', $htaccess);
        self::assertStringContainsString('Service-Worker-Allowed "/"', $htaccess);
        self::assertStringContainsString('Cache-Control "no-cache, no-store, must-revalidate"', $htaccess);
    }
}
