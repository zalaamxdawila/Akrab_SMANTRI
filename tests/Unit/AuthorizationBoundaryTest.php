<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AuthorizationBoundaryTest extends TestCase
{
    public function testArticleMutationsAreScopedToTheAuthenticatedUksOwner(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/uks/kelola_artikel.php');

        self::assertStringContainsString('DELETE FROM artikel_edukasi WHERE id = ? AND uks_id = ?', $contents);
        self::assertStringContainsString('WHERE id = ? AND uks_id = ?', $contents);
        self::assertStringContainsString('WHERE uks_id = ? ORDER BY', $contents);
    }

    public function testReferralCanOnlyTargetAStudent(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/uks/cetak_rujukan.php');

        self::assertStringContainsString("u.role = 'siswa'", $contents);
        self::assertStringContainsString('$siswa_id = (int)', $contents);
    }

    public function testQrScannerCannotRedirectToScannedExternalUrl(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/uks/scan_qr.php');

        self::assertStringNotContainsString('window.location.href = decodedText', $contents);
        self::assertStringNotContainsString('decodedText.startsWith("http")', $contents);
        self::assertStringContainsString('encodeURIComponent(decodedText)', $contents);
    }

    public function testConsultationReplyLocksAndValidatesPendingStudentQuestion(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/app/Services/ConsultationService.php');

        self::assertStringContainsString("u.role = 'siswa'", $contents);
        self::assertStringContainsString("k.status = 'menunggu'", $contents);
        self::assertStringContainsString('FOR UPDATE', $contents);
    }

    public function testMigrationToolDoesNotReuseRuntimeDatabaseCredentials(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/tools/migrate.php');

        self::assertStringContainsString("requireEnvironmentValue('AKRAB_MIGRATION_DB_USER')", $contents);
        self::assertStringContainsString("requireEnvironmentValue('AKRAB_MIGRATION_DB_PASS')", $contents);
        self::assertStringNotContainsString("requireEnvironmentValue('AKRAB_DB_USER')", $contents);
        self::assertStringNotContainsString("requireEnvironmentValue('AKRAB_DB_PASS')", $contents);
    }
}
