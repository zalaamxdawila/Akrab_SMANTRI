<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CsrfFormCoverageTest extends TestCase
{
    public static function postFormPages(): array
    {
        return array_map(
            static fn (string $path): array => [$path],
            [
                'login.php',
                'register.php',
                'siswa/dashboard.php',
                'siswa/kalkulator_gizi.php',
                'siswa/konsultasi.php',
                'siswa/kuesioner.php',
                'siswa/profil.php',
                'uks/import_siswa.php',
                'uks/jawab_konsultasi.php',
                'uks/kelola_artikel.php',
                'uks/profil.php',
            ]
        );
    }

    #[DataProvider('postFormPages')]
    public function testEveryPostFormContainsACsrfField(string $relativePath): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/' . $relativePath);
        self::assertNotFalse($contents);

        preg_match_all('/<form\b[^>]*method=["\']post["\'][^>]*>(.{0,500})/is', $contents, $matches);
        self::assertNotEmpty($matches[0], "No POST form found in {$relativePath}");

        foreach ($matches[1] as $formOpening) {
            self::assertStringContainsString(
                'csrfInput()',
                $formOpening,
                "POST form without CSRF field in {$relativePath}"
            );
        }
    }

    public function testStateChangesAreNotExposedThroughGetLinks(): void
    {
        $dashboard = file_get_contents(dirname(__DIR__, 2) . '/siswa/dashboard.php');
        $articles = file_get_contents(dirname(__DIR__, 2) . '/uks/kelola_artikel.php');

        self::assertStringNotContainsString("\$_GET['minum_ttd']", $dashboard);
        self::assertStringNotContainsString('?minum_ttd=', $dashboard);
        self::assertStringNotContainsString("\$_GET['delete']", $articles);
        self::assertStringNotContainsString('?delete=', $articles);
    }
}
