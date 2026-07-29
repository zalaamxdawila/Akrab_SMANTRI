<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class IdentityWorkflowTest extends TestCase
{
    public function testPublicRegistrationCannotCreateUksRole(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/register.php');

        self::assertStringContainsString("['siswa', 'orangtua']", $contents);
        self::assertStringNotContainsString('value="uks"', $contents);
        self::assertStringNotContainsString('kode_rahasia', $contents);
        self::assertStringNotContainsString('AKRAB_UKS_REGISTRATION_CODE', $contents);
    }

    public function testPublicRegistrationIsRateLimitedWithoutStoringRawIp(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/register.php');

        self::assertStringContainsString('registration_attempts', $contents);
        self::assertStringContainsString('INTERVAL 15 MINUTE', $contents);
        self::assertStringContainsString('hash_hmac', $contents);
        self::assertStringContainsString("requireEnvironmentValue('AKRAB_RATE_LIMIT_KEY')", $contents);
        self::assertStringContainsString('http_response_code(429)', $contents);
    }

    public function testParentDashboardRequiresApprovedDatabaseLink(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/orangtua/dashboard.php');

        self::assertStringContainsString('parent_student_links', $contents);
        self::assertStringContainsString("psl.status = 'approved'", $contents);
        self::assertStringContainsString('psl.parent_id = ?', $contents);
        self::assertStringNotContainsString('$anak_username', $contents);
    }

    public function testApprovalWorkflowIsUksOnlyAndAudited(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/uks/kelola_tautan.php');

        self::assertStringContainsString("check_role('uks')", $contents);
        self::assertStringContainsString('recordAuditEvent', $contents);
        self::assertStringContainsString('FOR UPDATE', $contents);
        self::assertStringContainsString("['approved', 'rejected']", $contents);
    }

    public function testUksProvisioningIsCliOnlyAndHardcodesRole(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/tools/provision_uks.php');

        self::assertStringContainsString("PHP_SAPI !== 'cli'", $contents);
        self::assertStringContainsString("'uks'", $contents);
        self::assertStringContainsString('AKRAB_PROVISION_UKS_PASSWORD', $contents);
        self::assertStringContainsString('audit_log', $contents);
    }

    public function testPublicRegistrationCannotCreateSuperadmin(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/register.php');

        self::assertStringNotContainsString('value="superadmin"', $contents);
        self::assertStringNotContainsString('AKRAB_PROVISION_SUPERADMIN_PASSWORD', $contents);
    }
}
