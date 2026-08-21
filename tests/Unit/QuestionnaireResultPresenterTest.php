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
            'skor_faktor_internal' => 0,
            'skor_faktor_eksternal' => 15,
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
            'Makanan Pagi',
            $result['answers']['sections']['makan']['items'][0]['question']
        );
        self::assertSame(
            [1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
            $result['answer_charts']['sikap']['values']
        );
        self::assertSame(
            [1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
            $result['answer_charts']['pengetahuan']['values']
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

    public function testVersionThreeAnswersAlsoProduceDiagrams(): void
    {
        $snapshot = (new QuestionnaireAnswerSnapshot())->fromInput($this->visibleAnswers());
        $snapshot['version'] = '2026-08-21.v3';
        foreach (['sikap', 'pengetahuan'] as $section) {
            foreach ($snapshot['sections'][$section]['items'] as &$item) {
                unset($item['chart_value']);
            }
            unset($item);
        }

        $result = (new QuestionnaireResultPresenter())->forResult([
            'answers_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
        ], null);

        self::assertSame(array_fill(0, 10, 1), $result['answer_charts']['sikap']['values']);
        self::assertSame(array_fill(0, 10, 1), $result['answer_charts']['pengetahuan']['values']);
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
        $input = [
            'mens_sudah' => 'ya', 'mens_usia_th' => '12', 'mens_usia_bln' => '6',
            'mens_teratur' => 'ya', 'mens_lama' => '5', 'mens_jarak_siklus' => '28',
        ];
        foreach (range(1, 6) as $index) {
            $input['makan_' . $index] = 'kadang';
        }
        foreach (range(1, 10) as $index) {
            $input['gejala_' . $index] = '2';
            $input['sikap_' . $index] = '1';
            $input['pengetahuan_' . $index] = ['a'];
        }
        foreach (range(1, 5) as $index) {
            $input['faktor_internal_' . $index] = 'tidak';
            $input['faktor_eksternal_' . $index] = 'rendah';
        }

        return $input;
    }
}
