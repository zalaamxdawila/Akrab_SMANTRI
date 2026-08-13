<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireLabServiceIntegrationTest extends TestCase
{
    private PDO $pdo;
    private QuestionnaireLabService $service;

    protected function setUp(): void
    {
        foreach (['CLINICAL_RISK_ENABLED=true','CLINICAL_OWNER_APPROVED=true','CLINICAL_MODEL_APPROVED=true','CLINICAL_SPEC_VERSION=research-v1','CLINICAL_MODEL_VERSION=research-model-v1','CLINICAL_MODEL_CHECKSUM=' . str_repeat('a', 64)] as $setting) putenv($setting);
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec("CREATE TABLE kuesioner (id INTEGER PRIMARY KEY, user_id INTEGER, kadar_hb REAL, kadar_mchc REAL, kadar_mcv REAL, kadar_mch REAL, archived_at TEXT, created_at TEXT);
            CREATE TABLE hasil_deteksi (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER, questionnaire_id INTEGER, probabilitas_risiko REAL, kategori_risiko TEXT, model_version TEXT, model_checksum TEXT, tanggal TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP, archived_at TEXT);
            CREATE TABLE lab_change_requests (id INTEGER PRIMARY KEY AUTOINCREMENT, questionnaire_id INTEGER, student_id INTEGER, kadar_hb REAL, kadar_mchc REAL, kadar_mcv REAL, kadar_mch REAL, status TEXT DEFAULT 'pending', reviewed_by INTEGER, reviewer_role TEXT, reviewed_at TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP);
            INSERT INTO kuesioner VALUES (1, 7, NULL, NULL, NULL, NULL, NULL, '2026-08-14 10:00:00')");
        $this->service = new QuestionnaireLabService($this->pdo);
    }

    protected function tearDown(): void
    {
        foreach (['CLINICAL_RISK_ENABLED','CLINICAL_OWNER_APPROVED','CLINICAL_MODEL_APPROVED','CLINICAL_SPEC_VERSION','CLINICAL_MODEL_VERSION','CLINICAL_MODEL_CHECKSUM'] as $name) putenv($name);
    }

    public function testStudentCompletesLabOnceAndReceivesLogisticResult(): void
    {
        $result = $this->service->completeInitial(7, $this->lab(12, 33, 85, 29));
        self::assertSame('rendah', $result['category']);
        self::assertSame(12.0, (float)$this->pdo->query('SELECT kadar_hb FROM kuesioner WHERE id=1')->fetchColumn());
        self::assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM hasil_deteksi')->fetchColumn());

        $this->expectException(InvalidArgumentException::class);
        $this->service->completeInitial(7, $this->lab(13, 34, 86, 30));
    }

    public function testChangeWaitsForAuthorizedApprovalThenCreatesHistory(): void
    {
        $this->service->completeInitial(7, $this->lab(12, 33, 85, 29));
        $requestId = $this->service->requestChange(7, $this->lab(10, 30, 70, 23));
        self::assertSame(12.0, (float)$this->pdo->query('SELECT kadar_hb FROM kuesioner WHERE id=1')->fetchColumn());
        self::assertNotNull($this->service->pendingForStudent(7));

        $this->service->review($requestId, 99, 'uks', true);
        self::assertSame(10.0, (float)$this->pdo->query('SELECT kadar_hb FROM kuesioner WHERE id=1')->fetchColumn());
        self::assertSame(2, (int)$this->pdo->query('SELECT COUNT(*) FROM hasil_deteksi')->fetchColumn());
        self::assertSame('approved', $this->pdo->query('SELECT status FROM lab_change_requests')->fetchColumn());
    }

    public function testUnauthorizedReviewerCannotProcessRequest(): void
    {
        $this->service->completeInitial(7, $this->lab(12, 33, 85, 29));
        $requestId = $this->service->requestChange(7, $this->lab(10, 30, 70, 23));
        $this->expectException(RuntimeException::class);
        $this->service->review($requestId, 7, 'siswa', true);
    }

    private function lab(float $hb, float $mchc, float $mcv, float $mch): array
    {
        return ['kadar_hb'=>$hb,'kadar_mchc'=>$mchc,'kadar_mcv'=>$mcv,'kadar_mch'=>$mch];
    }
}
