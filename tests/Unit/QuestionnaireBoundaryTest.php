<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireBoundaryTest extends TestCase
{
    public function testQuestionnairePageDelegatesPersistenceToService(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');

        self::assertStringContainsString('new QuestionnaireService($pdo)', $contents);
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

    public function testLabSectionIsRequiredExplainedAndAccessible(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');

        self::assertStringContainsString('<fieldset', $contents);
        self::assertStringContainsString('name="lab_status"', $contents);
        self::assertStringContainsString('value="tersedia"', $contents);
        self::assertStringContainsString('value="belum_ada"', $contents);
        foreach (['lab-hb-help', 'lab-mchc-help', 'lab-mcv-help', 'lab-mch-help'] as $id) {
            self::assertStringContainsString('id="' . $id . '"', $contents);
            self::assertStringContainsString('aria-describedby="' . $id . '"', $contents);
        }
        self::assertStringContainsString('setLabRequirement', $contents);
    }

    public function testAutomaticIdentityFieldsAreRebuiltOnTheServer(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');

        self::assertStringContainsString('$submission = $_POST;', $contents);
        self::assertStringContainsString('$submission[\'inisial\'] = $inisial;', $contents);
        self::assertStringContainsString('$submission[\'pendidikan\'] = $pendidikan;', $contents);
        self::assertStringContainsString('$submission[\'jurusan\'] = $jurusan;', $contents);
        self::assertStringContainsString('$submission[\'tanggal_wawancara\'] = date(\'Y-m-d\');', $contents);
        self::assertStringContainsString('submit($user_id, $submission)', $contents);
    }

    public function testArchivedQuestionnairesDoNotExtendTheCooldown(): void
    {
        $questionnaire = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');
        $dashboard = file_get_contents(dirname(__DIR__, 2) . '/siswa/dashboard.php');

        self::assertStringContainsString('archived_at IS NULL', $questionnaire);
        self::assertStringContainsString('archived_at IS NULL', $dashboard);
    }

    public function testWizardRevealsHiddenInvalidFieldsBeforeSubmission(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');

        self::assertStringContainsString("form.addEventListener('invalid'", $contents);
        self::assertStringContainsString('showInvalidInput', $contents);
        self::assertStringContainsString("form.querySelector(':invalid')", $contents);
        self::assertStringContainsString("typeof invalidInput.reportValidity === 'function'", $contents);
        self::assertDoesNotMatchRegularExpression(
            '/<button type="submit"[^>]*class="[^"]*\\bbtn-next\\b[^"]*"/',
            $contents
        );
    }

    public function testQuestionnaireCanBeCollectedWithoutBypassingTheClinicalGate(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/siswa/kuesioner.php');

        self::assertStringContainsString('if ($clinicalRiskEnabled)', $contents);
        self::assertStringContainsString('->collect($user_id, $submission)', $contents);
        self::assertStringContainsString('hasil risiko belum tersedia', strtolower($contents));
        self::assertDoesNotMatchRegularExpression(
            '/<button type="submit"[^>]*\\bdisabled\\b/',
            $contents
        );
    }
}
