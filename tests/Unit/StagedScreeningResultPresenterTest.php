<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StagedScreeningResultPresenterTest extends TestCase
{
    public function testLowSymptomResultExplainsWhyRiskQuestionsStayClosed(): void
    {
        $result = (new StagedScreeningResultPresenter())->present([
            'tahap_screening' => 'gejala_selesai',
            'rerata_gejala' => '4.6',
            'persentase_faktor_risiko' => null,
            'hasil_screening' => 'gejala_di_bawah_ambang',
        ]);

        self::assertSame('Skor gejala di bawah ambang', $result['title']);
        self::assertSame('4,6', $result['symptom_average']);
        self::assertFalse($result['show_risk_score']);
        self::assertStringContainsString('tidak dibuka', $result['explanation']);
        self::assertStringContainsString('bukan diagnosis', $result['disclaimer']);
    }

    public function testRiskBelowSeventyFiveShowsAnemiaIndicationAndReferralPath(): void
    {
        $result = (new StagedScreeningResultPresenter())->present([
            'tahap_screening' => 'selesai',
            'rerata_gejala' => '6.2',
            'persentase_faktor_risiko' => '74.9',
            'hasil_screening' => 'terindikasi_anemia',
        ]);

        self::assertSame('Terindikasi risiko anemia', $result['title']);
        self::assertTrue($result['show_risk_score']);
        self::assertSame('74,9%', $result['risk_percentage']);
        self::assertStringContainsString('UKS', implode(' ', $result['recommendations']));
        self::assertStringContainsString('Puskesmas', implode(' ', $result['recommendations']));
        self::assertStringContainsString('dokter', implode(' ', $result['recommendations']));
    }

    public function testRiskAtSeventyFiveIsNotIndicatedButSymptomsStillNeedMonitoring(): void
    {
        $result = (new StagedScreeningResultPresenter())->present([
            'tahap_screening' => 'selesai',
            'rerata_gejala' => '5.0',
            'persentase_faktor_risiko' => '75.0',
            'hasil_screening' => 'tidak_terindikasi_anemia',
        ]);

        self::assertSame('Belum terindikasi risiko anemia', $result['title']);
        self::assertSame('75,0%', $result['risk_percentage']);
        self::assertStringContainsString('pantau', strtolower($result['explanation']));
    }

    public function testPresenterRejectsIncompleteRiskStage(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new StagedScreeningResultPresenter())->present([
            'tahap_screening' => 'faktor_risiko_tersedia',
            'rerata_gejala' => '5.1',
            'persentase_faktor_risiko' => null,
        ]);
    }
}
