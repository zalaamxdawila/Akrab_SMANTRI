<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireBoundaryTest extends TestCase
{
    public function testQuestionnairePageDelegatesPersistenceToService(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');

        self::assertStringContainsString('new StagedScreeningService($store)', $contents);
        self::assertStringContainsString('new PdoStagedScreeningStore($pdo)', $contents);
        self::assertStringNotContainsString('INSERT INTO kuesioner', $contents);
        self::assertStringNotContainsString('beginTransaction()', $contents);
    }

    public function testServiceOwnsRiskEvaluationAndAtomicWrites(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/app/Services/QuestionnaireService.php');

        self::assertStringContainsString('validateQuestionnaireInput', $contents);
        self::assertStringContainsString('beginTransaction()', $contents);
        self::assertStringContainsString('rollBack()', $contents);
        self::assertStringContainsString('model_checksum', $contents);
    }

    public function testFirstStageHasProfileAndSymptomsWithoutLabSection(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');

        self::assertStringContainsString('<fieldset', $contents);
        self::assertStringContainsString('name="tanggal_lahir"', $contents);
        self::assertStringContainsString('name="pendidikan"', $contents);
        self::assertStringContainsString('name="jenis_kelamin"', $contents);
        self::assertStringContainsString('name="gejala_<?= $number ?>"', $contents);
        self::assertStringNotContainsString('name="lab_status"', $contents);
        self::assertStringNotContainsString('kadar_hb', $contents);
    }

    public function testIdentityAndOwnershipAreResolvedOnTheServer(): void
    {
        $route = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');
        $store = file_get_contents(dirname(__DIR__, 2) . '/app/Repositories/PdoStagedScreeningStore.php');

        self::assertStringContainsString("SELECT nama, username, kelas FROM users WHERE id = ?", $route);
        self::assertStringContainsString("role = 'siswa' AND status = 'active'", $store);
        self::assertStringContainsString('submitSymptoms(', $route);
        self::assertStringContainsString('createSymptomScreening(', file_get_contents(dirname(__DIR__, 2) . '/app/Services/StagedScreeningService.php'));
        self::assertStringContainsString('WHERE id = ? AND user_id = ?', $store);
    }

    public function testArchivedQuestionnairesDoNotExtendTheCooldown(): void
    {
        $questionnaire = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');
        $dashboard = file_get_contents(dirname(__DIR__, 2) . '/siswa/dashboard.php');

        self::assertStringContainsString('archived_at IS NULL', $questionnaire);
        self::assertStringContainsString('archived_at IS NULL', $dashboard);
    }

    public function testConditionalMenstrualFieldsAreRevealedAndRequired(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');

        self::assertStringContainsString('id="menstrualDetails"', $contents);
        self::assertStringContainsString("input.disabled = !started", $contents);
        self::assertStringContainsString("input.required = started", $contents);
        self::assertStringContainsString("form.checkValidity()", $contents);
    }

    public function testStagedScreeningDoesNotDependOnTheLabClinicalGate(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');

        self::assertStringContainsString('submitSymptoms(', $contents);
        self::assertStringContainsString('submitRiskFactors(', $contents);
        self::assertStringContainsString('tanpa pemeriksaan Hb', $contents);
        self::assertStringNotContainsString('isClinicalRiskEnabled()', $contents);
        self::assertDoesNotMatchRegularExpression(
            '/<button type="submit"[^>]*\\bdisabled\\b/',
            $contents
        );
    }
}
