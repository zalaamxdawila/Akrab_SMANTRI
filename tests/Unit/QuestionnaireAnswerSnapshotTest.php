<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireAnswerSnapshotTest extends TestCase
{
    public function testSnapshotContainsAllQuestionsVisibleToStudent(): void
    {
        $input = $this->validInput();
        $input['sikap_6'] = '4';
        $input['pengetahuan_2'] = ['a'];

        $snapshot = (new QuestionnaireAnswerSnapshot())->fromInput($input);
        $encoded = json_encode($snapshot, JSON_THROW_ON_ERROR);

        self::assertSame('2026-08-21.v5', $snapshot['version']);
        self::assertSame(
            ['makan', 'gejala', 'menstruasi', 'sikap', 'pengetahuan'],
            array_keys($snapshot['sections'])
        );
        self::assertCount(11, $snapshot['sections']['makan']['items']);
        self::assertCount(10, $snapshot['sections']['gejala']['items']);
        self::assertCount(10, $snapshot['sections']['sikap']['items']);
        self::assertCount(10, $snapshot['sections']['pengetahuan']['items']);
        self::assertStringContainsString('sikap_6', $encoded);
        self::assertStringContainsString('pengetahuan_2', $encoded);
    }

    public function testSnapshotPreservesHumanReadableQuestionsAndAnswers(): void
    {
        $input = $this->validInput();
        $input['makan_1'] = 'kadang';
        $input['gejala_1'] = '7';
        $input['sikap_1'] = '4';
        $input['pengetahuan_1'] = ['a', 'b'];
        $input['pengetahuan_9'] = ['a', 'g'];
        $input['pengetahuan_9_other'] = 'Bayam';

        $snapshot = (new QuestionnaireAnswerSnapshot())->fromInput($input);

        self::assertSame(
            'Kadang-kadang',
            $snapshot['sections']['makan']['items'][5]['answer']
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
            'Tahu, lanjut ke pertanyaan no 2, Tidak',
            $snapshot['sections']['pengetahuan']['items'][0]['answer']
        );
        self::assertSame(
            'Hati ayam, Lain-lain: Bayam',
            $snapshot['sections']['pengetahuan']['items'][8]['answer']
        );
        self::assertSame(4, $snapshot['sections']['sikap']['items'][0]['chart_value']);
        self::assertSame(2, $snapshot['sections']['pengetahuan']['items'][8]['chart_value']);
    }

    public function testSnapshotRejectsAnswersOutsideVisibleAllowlist(): void
    {
        $input = $this->validInput();
        $input['pengetahuan_6'] = ['k'];

        $this->expectException(InvalidArgumentException::class);
        (new QuestionnaireAnswerSnapshot())->fromInput($input);
    }

    /** @return array<string, mixed> */
    private function validInput(): array
    {
        $input = [
            'mens_sudah' => 'ya', 'mens_usia_th' => '12', 'mens_usia_bln' => '6',
            'mens_teratur' => 'ya', 'mens_lama' => '5', 'mens_jarak_siklus' => '28',
        ];
        foreach (range(1, 6) as $index) {
            $input['makan_' . $index] = 'selalu';
        }
        foreach (range(1, 10) as $index) {
            $input['gejala_' . $index] = '0';
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
