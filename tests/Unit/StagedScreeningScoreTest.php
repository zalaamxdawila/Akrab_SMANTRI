<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StagedScreeningScoreTest extends TestCase
{
    public function testSymptomAverageAtBoundaryDoesNotOpenRiskFactors(): void
    {
        $score = (new StagedScreeningScore())->symptoms([
            4, 4, 4, 4, 5, 5, 5, 5, 5, 5,
        ]);

        self::assertSame(4.6, $score['average']);
        self::assertFalse($score['risk_eligible']);
    }

    public function testOnlyAverageAboveBoundaryOpensRiskFactors(): void
    {
        $score = (new StagedScreeningScore())->symptoms([
            4, 4, 4, 5, 5, 5, 5, 5, 5, 5,
        ]);

        self::assertSame(4.7, $score['average']);
        self::assertTrue($score['risk_eligible']);
    }

    public function testSymptomsRejectWrongCountAndOutOfRangeValues(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new StagedScreeningScore())->symptoms([0, 1, 2, 3, 4, 5, 6, 7, 8, 11]);
    }

    public function testRiskScoreUsesTwoTransparentEqualWeightDimensions(): void
    {
        $score = (new StagedScreeningScore())->riskFactors([
            'mens_sudah' => 'ya',
            'mens_teratur' => 'ya',
            'makan_1' => 'kadang',
            'makan_2' => 'kadang',
            'makan_3' => 'kadang',
            'makan_4' => 'kadang',
            'makan_5' => 'kadang',
            'makan_6' => 'kadang',
        ]);

        self::assertSame(100.0, $score['internal_percentage']);
        self::assertSame(50.0, $score['external_percentage']);
        self::assertSame(75.0, $score['percentage']);
        self::assertFalse($score['anemia_indicated']);
    }

    public function testRiskPercentageBelowSeventyFiveIsIndicated(): void
    {
        $score = (new StagedScreeningScore())->riskFactors([
            'mens_sudah' => 'ya',
            'mens_teratur' => 'tidak',
            'makan_1' => 'selalu',
            'makan_2' => 'selalu',
            'makan_3' => 'selalu',
            'makan_4' => 'selalu',
            'makan_5' => 'selalu',
            'makan_6' => 'selalu',
        ]);

        self::assertSame(0.0, $score['internal_percentage']);
        self::assertSame(100.0, $score['external_percentage']);
        self::assertSame(50.0, $score['percentage']);
        self::assertTrue($score['anemia_indicated']);
    }

    public function testNotYetMenstruatingDoesNotCreateAnIrregularityPenalty(): void
    {
        $score = (new StagedScreeningScore())->riskFactors([
            'mens_sudah' => 'belum',
            'mens_teratur' => null,
            'makan_1' => 'selalu',
            'makan_2' => 'selalu',
            'makan_3' => 'selalu',
            'makan_4' => 'selalu',
            'makan_5' => 'selalu',
            'makan_6' => 'selalu',
        ]);

        self::assertSame(100.0, $score['percentage']);
        self::assertFalse($score['anemia_indicated']);
    }
}
