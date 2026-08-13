<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StudentOnboardingTest extends TestCase
{
    public function testStudentOnboardingPriorityIsEmailThenQuestionnaireThenLab(): void
    {
        self::assertSame('siswa/lengkapi_email.php', studentOnboardingDestination([
            'email' => null, 'questionnaire_id' => null,
        ]));
        self::assertSame('siswa/kuesioner.php', studentOnboardingDestination([
            'email' => 'siswa@example.sch.id', 'questionnaire_id' => null,
        ]));
        self::assertSame('siswa/data_laboratorium.php', studentOnboardingDestination([
            'email' => 'siswa@example.sch.id', 'questionnaire_id' => 12,
            'kadar_hb' => '12', 'kadar_mch' => null,
            'kadar_mchc' => '33', 'kadar_mcv' => '85',
        ]));
        self::assertNull(studentOnboardingDestination([
            'email' => 'siswa@example.sch.id', 'questionnaire_id' => 12,
            'kadar_hb' => '12', 'kadar_mch' => '29',
            'kadar_mchc' => '33', 'kadar_mcv' => '85',
        ]));
    }

    public function testEmailCompletionRouteHasSecurityControls(): void
    {
        $route = file_get_contents(dirname(__DIR__, 2) . '/siswa/lengkapi_email.php');
        self::assertNotFalse($route);
        self::assertStringContainsString("check_role('siswa')", $route);
        self::assertStringContainsString('FILTER_VALIDATE_EMAIL', $route);
        self::assertStringContainsString('UPDATE users SET email = ?', $route);
        self::assertStringContainsString('recordAuditEvent', $route);
    }

    public function testForgotPasswordAcceptsEmailWithoutRevealingAccountExistence(): void
    {
        $route = file_get_contents(dirname(__DIR__, 2) . '/lupa_password.php');
        self::assertStringContainsString('username = ? OR email = ?', $route);
        self::assertStringContainsString('Jika akun terdaftar', $route);
    }
}
