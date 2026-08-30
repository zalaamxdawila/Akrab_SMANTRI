<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireAggregatePresenterTest extends TestCase
{
    public function testItAggregatesEveryQuestionChoiceAcrossValidSnapshots(): void
    {
        $first = $this->answers('selalu', 2, 1, ['a']);
        $second = $this->answers('kadang', 7, 4, ['b']);
        $snapshot = new QuestionnaireAnswerSnapshot();

        $aggregate = (new QuestionnaireAggregatePresenter())->forSnapshots([
            $snapshot->encode($first),
            $snapshot->encode($second),
            null,
            '{"invalid":true}',
        ]);

        self::assertSame(2, $aggregate['responses_with_answers']);
        self::assertCount(10, $aggregate['charts']['gejala']);
        self::assertCount(10, $aggregate['charts']['sikap']);
        self::assertCount(10, $aggregate['charts']['pengetahuan']);
        self::assertCount(6, $aggregate['charts']['makan']);
        self::assertSame(
            [0, 0, 1, 0, 0, 0, 0, 1, 0, 0, 0],
            $aggregate['charts']['gejala'][0]['values']
        );
        self::assertSame(
            [1, 0, 0, 1],
            $aggregate['charts']['sikap'][0]['values']
        );
        self::assertSame(
            [1, 1],
            $aggregate['charts']['pengetahuan'][0]['values']
        );
        self::assertSame(
            [0, 1, 1],
            $aggregate['charts']['makan'][0]['values']
        );
    }

    /** @return array<string, mixed> */
    private function answers(
        string $diet,
        int $symptom,
        int $attitude,
        array $knowledge
    ): array {
        $input = [
            'mens_sudah' => 'ya', 'mens_usia_th' => '12', 'mens_usia_bln' => '0',
            'mens_teratur' => 'ya', 'mens_lama' => '5', 'mens_jarak_siklus' => '28',
        ];
        foreach (range(1, 6) as $index) $input['makan_' . $index] = $diet;
        foreach (range(1, 10) as $index) {
            $input['gejala_' . $index] = (string) $symptom;
            $input['sikap_' . $index] = (string) $attitude;
            $input['pengetahuan_' . $index] = $knowledge;
        }
        return $input;
    }
}
