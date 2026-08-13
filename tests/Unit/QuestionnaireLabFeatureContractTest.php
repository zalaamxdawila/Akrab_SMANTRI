<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireLabFeatureContractTest extends TestCase
{
    public function testMigrationAndSchemaDefineApprovalWorkflow(): void
    {
        $root = dirname(__DIR__, 2);
        $migration = file_get_contents($root . '/database/migrations/017_lab_change_requests.php');
        $schema = file_get_contents($root . '/database/schema.sql');

        self::assertStringContainsString('lab_change_requests', $migration);
        self::assertStringContainsString("'pending', 'approved', 'rejected'", $migration);
        self::assertStringContainsString('reviewer_role', $schema);
    }

    public function testStudentAndReviewerRoutesEnforceExpectedRoles(): void
    {
        $root = dirname(__DIR__, 2);
        $student = file_get_contents($root . '/siswa/data_laboratorium.php');
        $uks = file_get_contents($root . '/uks/permintaan_lab.php');
        $superadmin = file_get_contents($root . '/superadmin/lab_requests.php');

        self::assertStringContainsString("check_role('siswa')", $student);
        self::assertStringContainsString('requestChange', $student);
        self::assertStringContainsString("check_role('uks')", $uks);
        self::assertStringContainsString("'uks'", $uks);
        self::assertStringContainsString('SuperadminGuard::authorize', $superadmin);
        self::assertStringContainsString("'superadmin'", $superadmin);
    }

    public function testResultViewExplainsResearchModelAndDisclaimer(): void
    {
        $view = file_get_contents(dirname(__DIR__, 2) . '/views/questionnaire_analytics.php');
        self::assertStringContainsString('Cara regresi logistik menghitung risiko', $view);
        self::assertStringContainsString('Fungsi sigmoid', $view);
        self::assertStringContainsString('bukan diagnosis medis', $view);
    }
}
