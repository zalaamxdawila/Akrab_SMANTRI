<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireInsightsTest extends TestCase
{
    public function testEveryQuestionnaireScoreHasAPlainLanguageExplanation(): void
    {
        $insights = new QuestionnaireInsights();
        $result = $insights->forResponse([
            'skor_gejala' => 70,
            'skor_makan' => 12,
            'skor_pengetahuan' => 28,
            'skor_sikap' => 30,
        ]);

        self::assertSame(
            ['gejala', 'makan', 'pengetahuan', 'sikap'],
            array_keys($result)
        );
        foreach ($result as $insight) {
            self::assertArrayHasKey('label', $insight);
            self::assertArrayHasKey('percentage', $insight);
            self::assertArrayHasKey('explanation', $insight);
            self::assertNotSame('', $insight['explanation']);
        }
        self::assertStringContainsString(
            'lebih banyak keluhan',
            strtolower($result['gejala']['explanation'])
        );
    }

    public function testHistoryChartNormalizesDifferentScoreScales(): void
    {
        $insights = new QuestionnaireInsights();
        $chart = $insights->historyChart([
            [
                'created_at' => '2026-07-01 08:00:00',
                'skor_gejala' => 50,
                'skor_makan' => 9,
                'skor_pengetahuan' => 20,
                'skor_sikap' => 20,
            ],
        ]);

        self::assertSame(['01 Jul 2026'], $chart['labels']);
        self::assertSame([50.0], $chart['series']['gejala']);
        self::assertSame([50.0], $chart['series']['makan']);
        self::assertSame([50.0], $chart['series']['pengetahuan']);
        self::assertSame([50.0], $chart['series']['sikap']);
    }

    public function testClinicalDisclaimerDoesNotClaimADiagnosis(): void
    {
        $disclaimer = (new QuestionnaireInsights())->disclaimer();

        self::assertStringContainsString('bukan diagnosis', strtolower($disclaimer));
        self::assertStringContainsString('tenaga kesehatan', strtolower($disclaimer));
    }
}
