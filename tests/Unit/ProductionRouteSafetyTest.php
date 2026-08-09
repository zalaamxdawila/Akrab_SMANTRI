<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ProductionRouteSafetyTest extends TestCase
{
    public function testAuthorizationFailuresDoNotExposeInternalReasons(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ([
            'superadmin/dashboard.php',
            'superadmin/health_records.php',
            'superadmin/login_as.php',
        ] as $path) {
            $contents = file_get_contents($root . '/' . $path);
            self::assertStringNotContainsString('->getMessage()', $contents, $path);
        }
    }

    public function testDestructiveQuestionnaireResetRouteIsNotDeployable(): void
    {
        self::assertFileDoesNotExist(
            dirname(__DIR__, 2) . '/superadmin/reset_kuesioner.php'
        );
    }

    public function testPasswordResetNeverAssignsOneSharedDefaultPassword(): void
    {
        $root = dirname(__DIR__, 2);
        $dashboard = file_get_contents($root . '/superadmin/dashboard.php');
        $processor = file_get_contents($root . '/superadmin/process_reset_request.php');

        self::assertStringNotContainsString('reset_default', $dashboard);
        self::assertStringNotContainsString('reset_default', $processor);
        self::assertStringNotContainsString('Akrab@123', $dashboard . $processor);
    }

    public function testReleaseAllowlistExcludesLocalDiagnosticsAndMailExperiments(): void
    {
        $include = file_get_contents(
            dirname(__DIR__, 2) . '/deployment/include.txt'
        );

        foreach ([
            'check_enabled.php',
            'check_superadmin.php',
            'tools/nodemailer/',
            'vendor_manual/PHPMailer/',
        ] as $path) {
            self::assertDoesNotMatchRegularExpression(
                '/^' . preg_quote($path, '/') . '$/m',
                $include,
                "Release allowlist must exclude {$path}"
            );
        }
    }

    public function testQuestionnaireErrorPathDoesNotExposeDatabaseDetails(): void
    {
        $contents = file_get_contents(
            dirname(__DIR__, 2) . '/siswa/kuesioner.php'
        );

        self::assertStringNotContainsString(
            '"Gagal menyimpan: " . $e->getMessage()',
            $contents
        );
        self::assertStringContainsString(
            '$e instanceof InvalidArgumentException ? $e->getMessage() : publicErrorMessage()',
            $contents
        );
    }
}
