<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireAnswerSnapshotTest extends TestCase
{
    public function testSnapshotContainsOnlyQuestionsVisibleToStudent(): void
    {
        $input = $this->validInput();
        $input['sikap_6'] = '4';
        $input['pengetahuan_2'] = ['a'];

        $snapshot = (new QuestionnaireAnswerSnapshot())->fromInput($input);
        $encoded = json_encode($snapshot, JSON_THROW_ON_ERROR);

        self::assertSame('2026-08-17.v1', $snapshot['version']);
        self::assertSame(
            ['makan', 'gejala', 'sikap', 'pengetahuan'],
            array_keys($snapshot['sections'])
        );
        self::assertCount(6, $snapshot['sections']['makan']['items']);
        self::assertCount(10, $snapshot['sections']['gejala']['items']);
        self::assertCount(5, $snapshot['sections']['sikap']['items']);
        self::assertCount(1, $snapshot['sections']['pengetahuan']['items']);
        self::assertStringNotContainsString('sikap_6', $encoded);
        self::assertStringNotContainsString('pengetahuan_2', $encoded);
    }

    public function testSnapshotPreservesHumanReadableQuestionsAndAnswers(): void
    {
        $input = $this->validInput();
        $input['makan_1'] = 'kadang';
        $input['gejala_1'] = '7';
        $input['sikap_1'] = '4';
        $input['pengetahuan_1'] = ['a', 'b'];

        $snapshot = (new QuestionnaireAnswerSnapshot())->fromInput($input);

        self::assertSame(
            'Kadang-kadang',
            $snapshot['sections']['makan']['items'][0]['answer']
        );
        self::assertSame(
            '7 dari 10',
            $snapshot['sections']['gejala']['items'][0]['answer']
        );
        self::assertSame(
            'Sangat Setuju',
            $snapshot['sections']['sikap']['items'][0]['answer']
        );
        self::assertSame(
            'Zat Besi (Fe), Asam Folat',
            $snapshot['sections']['pengetahuan']['items'][0]['answer']
        );
    }

    public function testSnapshotRejectsAnswersOutsideVisibleAllowlist(): void
    {
        $input = $this->validInput();
        $input['pengetahuan_1'] = ['c'];

        $this->expectException(InvalidArgumentException::class);
        (new QuestionnaireAnswerSnapshot())->fromInput($input);
    }

    /** @return array<string, mixed> */
    private function validInput(): array
    {
        $input = ['pengetahuan_1' => ['a']];
        foreach (range(1, 6) as $index) {
            $input['makan_' . $index] = 'selalu';
        }
        foreach (range(1, 10) as $index) {
            $input['gejala_' . $index] = '0';
        }
        foreach (range(1, 5) as $index) {
            $input['sikap_' . $index] = '1';
        }

        return $input;
    }
}
