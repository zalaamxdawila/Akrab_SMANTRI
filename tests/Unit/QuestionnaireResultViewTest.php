<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/views/questionnaire_analytics.php';

final class QuestionnaireResultViewTest extends TestCase
{
    public function testCompletionChartComparesUniqueRespondentsWithActiveStudents(): void
    {
        ob_start();
        renderQuestionnaireCompletionChart('completionChart', 7, 3);
        $html = (string) ob_get_clean();

        self::assertStringContainsString('Sudah Mengisi', $html);
        self::assertStringContainsString('Belum Mengisi', $html);
        self::assertStringContainsString('data: [7,3]', $html);
        self::assertStringContainsString("type: 'doughnut'", $html);
    }

    public function testAggregateQuestionChartRendersResponseCountsWithUniqueCanvasIds(): void
    {
        ob_start();
        renderQuestionnaireChoiceCharts([
            'makan' => [[
                'key' => 'makan_1',
                'question' => 'Apakah sarapan?',
                'labels' => ['Tidak pernah', 'Kadang-kadang', 'Selalu'],
                'values' => [2, 3, 5],
                'selected' => [],
            ]],
        ], true, 10, 'aggregateQuestionChoiceChart');
        $html = (string) ob_get_clean();

        self::assertStringContainsString('Diagram Jawaban Setiap Pertanyaan', $html);
        self::assertStringContainsString('10 pengisian aktif', $html);
        self::assertStringContainsString('data: values', $html);
        self::assertStringContainsString('const values = [2,3,5]', $html);
        self::assertStringContainsString('aggregateQuestionChoiceChart-makan_1', $html);
        self::assertStringNotContainsString('Pilihan yang dipilih', $html);
    }

    public function testSharedViewRendersAllResponseRecapWithRawAndNormalizedScores(): void
    {
        $insights = (new QuestionnaireInsights())->forResponse([
            'skor_gejala' => 30,
            'skor_makan' => 9,
            'skor_pengetahuan' => 24,
            'skor_sikap' => 20,
        ]);

        ob_start();
        renderQuestionnaireAggregateRecap($insights, 6);
        $html = (string) ob_get_clean();

        self::assertStringContainsString('Rekapitulasi Semua Hasil', $html);
        self::assertStringContainsString('6 pengisian aktif', $html);
        self::assertStringContainsString('30 / 100', $html);
        self::assertStringContainsString('9 / 18', $html);
        self::assertStringContainsString('30%', $html);
        self::assertSame(3, substr_count($html, '50%'));
        self::assertStringContainsString('<table', $html);
    }

    public function testSharedViewRendersSummaryAndKeyboardAccessibleDetails(): void
    {
        $presentation = [
            'risk' => [
                'label' => 'Sedang', 'tone' => 'warning',
                'probability_label' => '55,0%', 'date_label' => '17 Agu 2026',
            ],
            'scores' => (new QuestionnaireInsights())->forResponse([
                'skor_gejala' => 40, 'skor_makan' => 12,
                'skor_pengetahuan' => 20, 'skor_sikap' => 20,
            ]),
            'priorities' => [[
                'key' => 'gejala', 'label' => 'Keluhan & gejala',
                'level' => 'Keluhan sedang',
                'explanation' => 'Ada beberapa keluhan.',
            ]],
            'actions' => ['Diskusikan hasil dengan petugas UKS.'],
            'answers' => [
                'available' => true,
                'message' => '',
                'version' => '2026-08-17.v1',
                'sections' => [
                    'gejala' => [
                        'label' => 'Keluhan dan gejala',
                        'items' => [[
                            'key' => 'gejala_1',
                            'question' => '<script>tidak aman</script>',
                            'answer' => '2 dari 10',
                        ]],
                    ],
                ],
            ],
            'answer_charts' => [
                'gejala' => [
                    'labels' => ['G1','G2','G3','G4','G5','G6','G7','G8','G9','G10'],
                    'questions' => array_fill(0, 10, 'Pertanyaan gejala'),
                    'values' => array_fill(0, 10, 2),
                    'max' => 10,
                ],
                'sikap' => [
                    'labels' => ['S1','S2','S3','S4','S5','S6','S7','S8','S9','S10'],
                    'questions' => array_fill(0, 10, 'Pertanyaan sikap'),
                    'values' => array_fill(0, 10, 3),
                    'max' => 4,
                ],
                'pengetahuan' => [
                    'labels' => ['P1','P2','P3','P4','P5','P6','P7','P8','P9','P10'],
                    'questions' => array_fill(0, 10, 'Pertanyaan pengetahuan'),
                    'values' => array_fill(0, 10, 1),
                    'max' => 10,
                ],
                'makan' => [
                    'labels' => ['M1','M2','M3','M4','M5','M6'],
                    'questions' => array_fill(0, 6, 'Pertanyaan pola makan'),
                    'values' => array_fill(0, 6, 2),
                    'max' => 3,
                ],
            ],
            'choice_charts' => [
                'sikap' => [[
                    'key' => 'sikap_1', 'question' => 'Pertanyaan sikap',
                    'labels' => ['Tidak Setuju', 'Kurang Setuju', 'Setuju', 'Sangat Setuju'],
                    'values' => [0, 0, 1, 0], 'selected' => ['Setuju'],
                ]],
                'pengetahuan' => [[
                    'key' => 'pengetahuan_1', 'question' => 'Pertanyaan pengetahuan',
                    'labels' => ['Tahu', 'Tidak'], 'values' => [1, 0], 'selected' => ['Tahu'],
                ]],
            ],
            'disclaimer' => 'Hasil ini bukan diagnosis medis.',
        ];

        ob_start();
        renderQuestionnaireResult($presentation, [
            'kadar_hb' => null, 'kadar_mchc' => null,
            'kadar_mcv' => null, 'kadar_mch' => null,
            'mens_sudah' => 'ya', 'mens_teratur' => 'ya',
            'mens_lama_hari' => 5, 'mens_jarak_siklus' => 28,
            'makanan_dikonsumsi' => 'Sayur',
        ]);
        $html = (string) ob_get_clean();

        self::assertStringContainsString('Hasil Ringkas', $html);
        self::assertStringContainsString('<details', $html);
        self::assertStringContainsString('answerAttitudeChart', $html);
        self::assertStringContainsString('answerKnowledgeChart', $html);
        self::assertStringContainsString('answerSymptomChart', $html);
        self::assertStringContainsString('answerDietChart', $html);
        self::assertStringContainsString('questionChoiceChart-sikap_1', $html);
        self::assertStringContainsString('questionChoiceChart-pengetahuan_1', $html);
        self::assertStringContainsString('Pilihan yang dipilih: Setuju', $html);
        self::assertStringContainsString('new Chart(', $html);
        self::assertStringContainsString('Hasil Lengkap', $html);
        self::assertStringContainsString('question-answer-overview', $html);
        self::assertStringContainsString('1 jawaban tercatat', $html);
        $detailsPosition = strpos($html, '<details');
        self::assertNotFalse($detailsPosition);
        self::assertGreaterThan(
            $detailsPosition,
            strpos($html, 'question-answer-overview')
        );
        self::assertGreaterThan(
            $detailsPosition,
            strpos($html, 'answerSymptomChart')
        );
        self::assertGreaterThan(
            $detailsPosition,
            strpos($html, 'logistic-model-title')
        );
        self::assertSame(1, substr_count($html, 'Pertanyaan dan jawaban'));
        self::assertStringContainsString('&lt;script&gt;tidak aman&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>tidak aman</script>', $html);
        self::assertStringContainsString('bukan diagnosis', $html);
    }
}
