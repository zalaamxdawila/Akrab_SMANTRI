<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/views/questionnaire_analytics.php';

final class QuestionnaireResultViewTest extends TestCase
{
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
        self::assertStringContainsString('Hasil Lengkap', $html);
        self::assertStringContainsString('question-answer-overview', $html);
        self::assertStringContainsString('1 jawaban tercatat', $html);
        self::assertLessThan(
            strpos($html, '<details'),
            strpos($html, 'question-answer-overview')
        );
        self::assertSame(1, substr_count($html, 'Pertanyaan dan jawaban'));
        self::assertStringContainsString('&lt;script&gt;tidak aman&lt;/script&gt;', $html);
        self::assertStringNotContainsString('<script>tidak aman</script>', $html);
        self::assertStringContainsString('bukan diagnosis', $html);
    }
}
