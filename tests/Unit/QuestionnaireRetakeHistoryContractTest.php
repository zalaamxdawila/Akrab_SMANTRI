<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireRetakeHistoryContractTest extends TestCase
{
    public function testMigrationKeepsPreviousQuestionnairesAsPersonalHistory(): void
    {
        $root = dirname(__DIR__, 2);
        $migration = file_get_contents(
            $root . '/database/migrations/022_questionnaire_retake_history.php'
        );
        $schema = file_get_contents($root . '/database/schema.sql');

        self::assertIsString($migration);
        self::assertStringContainsString(
            "'version' => '022_questionnaire_retake_history'",
            $migration
        );
        foreach ([
            'history_only_at',
            'history_only_by',
            'history_only_reason',
        ] as $column) {
            self::assertStringContainsString($column, $migration);
            self::assertStringContainsString($column, $schema);
        }
        self::assertStringContainsString('idx_kuesioner_primary_history', $migration);
        self::assertStringContainsString('idx_kuesioner_primary_history', $schema);
    }

    public function testResetServiceIsAtomicAuthorizedAndAudited(): void
    {
        $root = dirname(__DIR__, 2);
        $service = file_get_contents(
            $root . '/app/Services/QuestionnaireRetakeService.php'
        );
        $store = file_get_contents(
            $root . '/app/Repositories/PdoQuestionnaireRetakeStore.php'
        );

        self::assertIsString($service);
        self::assertIsString($store);
        self::assertStringContainsString("['uks', 'superadmin']", $service);
        self::assertStringContainsString('isImpersonating()', $service);
        self::assertStringContainsString('beginTransaction()', $store);
        self::assertStringContainsString("u.role = 'siswa'", $store);
        self::assertStringContainsString('history_only_at IS NULL', $store);
        self::assertStringContainsString(
            'SET history_only_at = CURRENT_TIMESTAMP',
            $store
        );
        self::assertStringContainsString('history_only_reason = ?', $store);
        self::assertStringContainsString('questionnaire.retake_enabled', $store);
        self::assertStringContainsString('ImpersonationMutationAudit', $store);
    }

    public function testPrimaryAnalyticsExcludeHistoryOnlyRowsButPersonalHistoryKeepsThem(): void
    {
        $root = dirname(__DIR__, 2);
        $analytics = file_get_contents(
            $root . '/app/Repositories/QuestionnaireAnalyticsRepository.php'
        );
        $stagedStore = file_get_contents(
            $root . '/app/Repositories/PdoStagedScreeningStore.php'
        );
        $dashboard = file_get_contents($root . '/siswa/dashboard.php');
        $onboarding = file_get_contents($root . '/helpers.php');

        self::assertIsString($analytics);
        self::assertStringContainsString('history_only_at IS NULL', $analytics);
        self::assertStringContainsString('latestPrimaryForStudent', $analytics);
        self::assertMatchesRegularExpression(
            '/historyForStudent\(int \$studentId\).*?archived_at IS NULL(?!.*history_only_at IS NULL)/s',
            $analytics
        );
        self::assertStringContainsString('history_only_at IS NULL', $stagedStore);
        self::assertStringContainsString('history_only_at IS NULL', $dashboard);
        self::assertStringContainsString('history_only_at IS NULL', $onboarding);
    }

    public function testOnlyUksAndSuperadminReceivePostOnlyResetControls(): void
    {
        $root = dirname(__DIR__, 2);
        $uksRoute = file_get_contents($root . '/uks/questionnaire_retake.php');
        $superadminRoute = file_get_contents(
            $root . '/superadmin/questionnaire_retake.php'
        );
        $uksDetail = file_get_contents($root . '/uks/detail_siswa.php');
        $superadminDetail = file_get_contents(
            $root . '/superadmin/questionnaire_results.php'
        );

        self::assertIsString($uksRoute);
        self::assertIsString($superadminRoute);
        self::assertStringContainsString("REQUEST_METHOD'] !== 'POST'", $uksRoute);
        self::assertStringContainsString("check_role('uks')", $uksRoute);
        self::assertStringContainsString('SuperadminGuard::authorize', $superadminRoute);
        self::assertStringContainsString('QuestionnaireRetakeService', $uksRoute);
        self::assertStringContainsString('QuestionnaireRetakeService', $superadminRoute);
        self::assertStringContainsString('csrfInput()', $uksDetail);
        self::assertStringContainsString('csrfInput()', $superadminDetail);
        self::assertStringContainsString('questionnaire_retake.php', $uksDetail);
        self::assertStringContainsString('questionnaire_retake.php', $superadminDetail);
        self::assertStringContainsString('name="reason"', $uksDetail);
        self::assertStringContainsString('name="reason"', $superadminDetail);
        self::assertStringContainsString('required', $uksDetail);
        self::assertStringContainsString('required', $superadminDetail);
    }

    public function testHistoryOnlyStatusIsExplainedInIndividualViews(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ([
            'siswa/dashboard.php',
            'siswa/hasil_deteksi.php',
            'uks/detail_siswa.php',
            'superadmin/questionnaire_results.php',
            'orangtua/dashboard.php',
        ] as $path) {
            $source = file_get_contents($root . '/' . $path);
            self::assertIsString($source);
            self::assertStringContainsString('Riwayat pribadi', $source, $path);
            self::assertStringContainsString('tidak dihitung', $source, $path);
        }
    }
}
