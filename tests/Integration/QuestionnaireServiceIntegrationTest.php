<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireServiceIntegrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('SQLite PDO driver is required for isolated integration tests.');
        }

        putenv('CLINICAL_RISK_ENABLED=true');
        putenv('CLINICAL_OWNER_APPROVED=true');
        putenv('CLINICAL_MODEL_APPROVED=true');
        putenv('CLINICAL_SPEC_VERSION=spec-v1');
        putenv('CLINICAL_MODEL_VERSION=model-v1');
        putenv('CLINICAL_MODEL_CHECKSUM=' . str_repeat('a', 64));

        $this->pdo = class_exists(\Pdo\Sqlite::class)
            ? new \Pdo\Sqlite('sqlite::memory:')
            : new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if ($this->pdo instanceof \Pdo\Sqlite) {
            $this->pdo->createFunction(
                'CURDATE',
                static fn (): string => date('Y-m-d')
            );
        } else {
            $this->pdo->sqliteCreateFunction(
                'CURDATE',
                static fn (): string => date('Y-m-d')
            );
        }
        $this->pdo->exec('CREATE TABLE kuesioner (user_id INTEGER, tanggal_wawancara TEXT, nomor_responden TEXT, inisial_responden TEXT, tanggal_lahir TEXT, tempat_lahir TEXT, alamat TEXT, pendidikan TEXT, kadar_hb REAL, kadar_mchc REAL, kadar_mcv REAL, kadar_mch REAL, skor_gejala INTEGER, skor_sikap INTEGER, skor_pengetahuan INTEGER, mens_sudah TEXT, mens_usia_th INTEGER, mens_teratur TEXT, mens_lama_hari INTEGER, mens_jarak_siklus INTEGER, skor_makan INTEGER, makanan_dikonsumsi TEXT)');
        $this->pdo->exec('CREATE TABLE hasil_deteksi (user_id INTEGER, probabilitas_risiko REAL, kategori_risiko TEXT, model_version TEXT, model_checksum TEXT, tanggal TEXT)');
    }

    protected function tearDown(): void
    {
        foreach (['CLINICAL_RISK_ENABLED', 'CLINICAL_OWNER_APPROVED', 'CLINICAL_MODEL_APPROVED', 'CLINICAL_SPEC_VERSION', 'CLINICAL_MODEL_VERSION', 'CLINICAL_MODEL_CHECKSUM'] as $name) {
            putenv($name);
        }
    }

    public function testSubmissionWritesQuestionnaireAndVersionedRiskAtomically(): void
    {
        $input = [
            'inisial' => 'AB', 'pendidikan' => 'Kelas X', 'mens_sudah' => 'ya', 'mens_teratur' => 'ya',
            'mens_usia_th' => '12', 'mens_lama' => '5',
            'lab_status' => 'tersedia', 'kadar_hb' => '11',
            'kadar_mchc' => '33', 'kadar_mcv' => '82', 'kadar_mch' => '28',
        ];
        for ($i = 1; $i <= 10; $i++) {
            $input['gejala_' . $i] = '0';
            $input['sikap_' . $i] = '0';
            $input['pengetahuan_' . $i] = [];
        }
        foreach (range(1, 6) as $i) {
            $input['makan_' . $i] = 'kadang';
        }

        $result = (new QuestionnaireService($this->pdo))->submit(42, $input);

        self::assertSame('model-v1', $result['model_version']);
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM kuesioner')->fetchColumn());
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM hasil_deteksi')->fetchColumn());
    }
}
