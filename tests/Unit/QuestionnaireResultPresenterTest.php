<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireResultPresenterTest extends TestCase
{
    public function testPresenterBuildsClearSummaryAndCompleteAnswerDetails(): void
    {
        $snapshot = (new QuestionnaireAnswerSnapshot())->encode(
            $this->visibleAnswers()
        );
        $result = (new QuestionnaireResultPresenter())->forResult([
            'skor_gejala' => 80,
            'skor_makan' => 6,
            'skor_pengetahuan' => 30,
            'skor_sikap' => 32,
            'answers_snapshot' => $snapshot,
        ], [
            'kategori_risiko' => 'tinggi',
            'probabilitas_risiko' => 0.8123,
            'tanggal' => '2026-08-17',
        ]);

        self::assertSame('Tinggi', $result['risk']['label']);
        self::assertSame('81,2%', $result['risk']['probability_label']);
        self::assertSame('gejala', $result['priorities'][0]['key']);
        self::assertCount(3, $result['priorities']);
        self::assertGreaterThanOrEqual(3, count($result['actions']));
        self::assertTrue($result['answers']['available']);
        self::assertSame(
            'Sarapan pagi',
            $result['answers']['sections']['makan']['items'][0]['question']
        );
        self::assertStringContainsString(
            'bukan diagnosis',
            strtolower($result['disclaimer'])
        );
    }

    public function testHistoricalResponseExplainsThatAnswersAreUnavailable(): void
    {
        $result = (new QuestionnaireResultPresenter())->forResult([
            'skor_gejala' => 10,
            'skor_makan' => 12,
            'skor_pengetahuan' => 20,
            'skor_sikap' => 20,
            'answers_snapshot' => null,
        ], null);

        self::assertFalse($result['answers']['available']);
        self::assertStringContainsString(
            'pengisian lama',
            strtolower($result['answers']['message'])
        );
        self::assertSame('Belum tersedia', $result['risk']['label']);
    }

    public function testCorruptSnapshotFailsClosedWithoutRenderingRawContent(): void
    {
        $result = (new QuestionnaireResultPresenter())->forResult([
            'skor_gejala' => 10,
            'skor_makan' => 12,
            'skor_pengetahuan' => 20,
            'skor_sikap' => 20,
            'answers_snapshot' => '{"sections":"<script>alert(1)</script>"}',
        ], null);

        self::assertFalse($result['answers']['available']);
        self::assertStringNotContainsString(
            '<script>',
            $result['answers']['message']
        );
    }

    /** @return array<string, mixed> */
    private function visibleAnswers(): array
    {
        $input = ['pengetahuan_1' => ['a']];
        foreach (range(1, 6) as $index) {
            $input['makan_' . $index] = 'kadang';
        }
        foreach (range(1, 10) as $index) {
            $input['gejala_' . $index] = '2';
        }
        foreach (range(1, 5) as $index) {
            $input['sikap_' . $index] = '3';
        }

        return $input;
    }
}
