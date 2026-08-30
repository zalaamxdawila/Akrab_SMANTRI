<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StudentOnboardingTest extends TestCase
{
    public function testExistingStudentWithoutEmailIsNotForcedToCompleteIt(): void
    {
        self::assertSame('siswa/kuesioner.php', studentOnboardingDestination([
            'email' => null, 'questionnaire_id' => null,
        ]));
        self::assertNull(studentOnboardingDestination([
            'email' => null, 'questionnaire_id' => 12,
        ]));
        self::assertSame('siswa/kuesioner.php', studentOnboardingDestination([
            'email' => 'siswa@example.sch.id', 'questionnaire_id' => null,
        ]));
        self::assertNull(studentOnboardingDestination([
            'email' => 'siswa@example.sch.id', 'questionnaire_id' => 12,
        ]));
    }

    public function testNewPublicRegistrationRequiresEmailOnClientAndServer(): void
    {
        $route = file_get_contents(dirname(__DIR__, 2) . '/register.php');

        self::assertIsString($route);
        self::assertStringContainsString("if (empty(\$email))", $route);
        self::assertStringContainsString('Email wajib diisi untuk akun baru.', $route);
        self::assertMatchesRegularExpression(
            '/<input[^>]+type="email"[^>]+name="email"[^>]+required/',
            $route
        );
        self::assertStringNotContainsString('(Opsional)', $route);
    }

    public function testDashboardShowsClosableEmailBubbleOnProfileNavigation(): void
    {
        $root = dirname(__DIR__, 2);
        $dashboard = file_get_contents(dirname(__DIR__, 2) . '/siswa/dashboard.php');
        $script = file_get_contents($root . '/assets/js/email-profile-notice.js');
        $stylesheet = file_get_contents($root . '/assets/css/style.css');

        self::assertIsString($dashboard);
        self::assertIsString($script);
        self::assertIsString($stylesheet);
        self::assertStringContainsString("if (!\$hasEmail)", $dashboard);
        self::assertStringContainsString('data-lucide="circle-user-round"', $dashboard);
        self::assertStringContainsString('class="profile-nav-link', $dashboard);
        self::assertStringContainsString('data-email-profile-notice', $dashboard);
        self::assertStringContainsString('data-email-notice-close', $dashboard);
        self::assertStringContainsString('aria-label="Tutup pengingat email"', $dashboard);
        self::assertStringContainsString('Email belum dilengkapi.', $dashboard);
        self::assertStringContainsString('agar dapat mereset password jika lupa', $dashboard);
        self::assertStringContainsString('href="profil.php"', $dashboard);
        self::assertStringContainsString('email-profile-notice.js', $dashboard);
        self::assertStringNotContainsString(
            'alert alert-warning d-flex align-items-center gap-2 shadow-sm border-warning',
            $dashboard
        );

        self::assertStringContainsString('sessionStorage', $script);
        self::assertStringContainsString("addEventListener('click'", $script);
        self::assertStringContainsString('notice.hidden = true', $script);
        self::assertStringContainsString('.profile-email-bubble', $stylesheet);
        self::assertStringContainsString('.profile-email-dot', $stylesheet);
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
