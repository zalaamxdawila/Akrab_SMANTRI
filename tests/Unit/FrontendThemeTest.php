<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class FrontendThemeTest extends TestCase
{
    public function testThemeUsesSystemPreferenceAndAccessibleNavbarToggle(): void
    {
        $script = file_get_contents(
            dirname(__DIR__, 2) . '/assets/js/app-init.js'
        );

        self::assertStringContainsString('prefers-color-scheme: dark', $script);
        self::assertStringContainsString('aria-pressed', $script);
        self::assertStringContainsString('aria-label', $script);
        self::assertStringContainsString("document.querySelector('.navbar')", $script);
    }

    public function testNavbarScrollStateDoesNotForceLightInlineColors(): void
    {
        $script = file_get_contents(
            dirname(__DIR__, 2) . '/assets/js/main.js'
        );

        self::assertStringContainsString("classList.toggle('is-scrolled'", $script);
        self::assertStringNotContainsString(
            "navbar.style.background",
            $script
        );
    }

    public function testSharedThemeCoversInteractiveBootstrapSurfaces(): void
    {
        $css = file_get_contents(
            dirname(__DIR__, 2) . '/assets/css/style.css'
        );

        foreach ([
            '--surface:',
            '--surface-muted:',
            '--border-color:',
            '[data-bs-theme="dark"]',
            '.form-control',
            '.form-select',
            '.table',
            '.modal-content',
            '.alert',
            '.navbar.is-scrolled',
        ] as $contract) {
            self::assertStringContainsString($contract, $css);
        }
    }

    public function testNavbarMarkupHasNoDuplicateBootstrapToggleAttribute(): void
    {
        $root = dirname(__DIR__, 2);
        $duplicateAttribute = 'data-bs-toggle="collapse" ' . 'data-bs-toggle="target"';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $root,
                FilesystemIterator::SKIP_DOTS
            )
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            self::assertStringNotContainsString(
                $duplicateAttribute,
                file_get_contents($file->getPathname()),
                $file->getPathname()
            );
        }
    }

    public function testLandingNavbarUsesBootstrapLargeBreakpointForMobileLayout(): void
    {
        $root = dirname(__DIR__, 2);
        $landing = (string) file_get_contents($root . '/index.php');
        $css = (string) file_get_contents($root . '/assets/css/style.css');

        self::assertStringContainsString('landing-navbar', $landing);
        self::assertStringContainsString('@media (max-width: 991.98px)', $css);
        self::assertStringContainsString('.landing-navbar .navbar-nav', $css);
        self::assertStringContainsString('.landing-navbar .nav-item', $css);
        self::assertStringContainsString('.landing-navbar .btn', $css);
    }
}
