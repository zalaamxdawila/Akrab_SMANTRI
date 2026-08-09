<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PwaInstallationGuideTest extends TestCase
{
    public function testLandingPageGuideIsPackagedAndOpensTheTrackedPdf(): void
    {
        $root = dirname(__DIR__, 2);
        $guide = 'Panduan_Instalasi_PWA_AKRAB.pdf';
        $landing = file_get_contents($root . '/index.php');
        $include = file_get_contents($root . '/deployment/include.txt');
        $exclude = file_get_contents($root . '/deployment/exclude.txt');
        $htaccess = file_get_contents($root . '/.htaccess');

        self::assertNotFalse($landing);
        self::assertNotFalse($include);
        self::assertNotFalse($exclude);
        self::assertNotFalse($htaccess);
        self::assertFileExists($root . '/' . $guide);
        self::assertGreaterThan(0, filesize($root . '/' . $guide));
        self::assertStringContainsString('href="' . $guide . '"', $landing);
        self::assertStringContainsString('target="_blank"', $landing);
        self::assertMatchesRegularExpression('/^' . preg_quote($guide, '/') . '$/m', $include);
        self::assertStringNotContainsString('*.pdf', $exclude);
        self::assertStringNotContainsString($guide, $htaccess);
    }
}
