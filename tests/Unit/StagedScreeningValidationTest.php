<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StagedScreeningValidationTest extends TestCase
{
    public function testValidatesProfileAndTenSymptomAnswers(): void
    {
        $input = $this->validSymptoms();
        $result = validateStagedSymptomInput($input);

        self::assertTrue($result['valid']);
        self::assertSame('Kelas IX', $result['values']['pendidikan']);
        self::assertSame('perempuan', $result['values']['jenis_kelamin']);
        self::assertCount(10, $result['values']['symptoms']);
    }

    public function testRejectsProfileOutsideStudentAgeRange(): void
    {
        $input = $this->validSymptoms();
        $input['tanggal_lahir'] = (new DateTimeImmutable('today'))
            ->modify('-30 years')
            ->format('Y-m-d');

        $result = validateStagedSymptomInput($input);

        self::assertFalse($result['valid']);
        self::assertStringContainsString('usia', strtolower($result['errors'][0]));
    }

    public function testMenstrualDetailsAreRequiredOnlyAfterMenstruationStarted(): void
    {
        $notStarted = $this->validRiskFactors();
        $notStarted['mens_sudah'] = 'belum';
        unset(
            $notStarted['mens_usia_th'],
            $notStarted['mens_usia_bln'],
            $notStarted['mens_teratur'],
            $notStarted['mens_lama'],
            $notStarted['mens_jarak_siklus']
        );

        $notStartedResult = validateStagedRiskFactorInput($notStarted);
        self::assertTrue($notStartedResult['valid']);
        self::assertNull($notStartedResult['values']['mens_teratur']);

        $started = $this->validRiskFactors();
        unset($started['mens_teratur']);
        $startedResult = validateStagedRiskFactorInput($started);
        self::assertFalse($startedResult['valid']);
    }

    public function testRejectsDietChoiceOutsideCanonicalQuestionnaireOptions(): void
    {
        $input = $this->validRiskFactors();
        $input['makan_4'] = 'sering';

        $result = validateStagedRiskFactorInput($input);

        self::assertFalse($result['valid']);
    }

    /** @return array<string, mixed> */
    private function validSymptoms(): array
    {
        $input = [
            'tanggal_lahir' => (new DateTimeImmutable('today'))
                ->modify('-15 years')
                ->format('Y-m-d'),
            'pendidikan' => 'Kelas IX',
            'jenis_kelamin' => 'perempuan',
        ];
        for ($index = 1; $index <= 10; $index++) {
            $input['gejala_' . $index] = '5';
        }
        return $input;
    }

    /** @return array<string, mixed> */
    private function validRiskFactors(): array
    {
        $input = [
            'mens_sudah' => 'ya',
            'mens_usia_th' => '12',
            'mens_usia_bln' => '0',
            'mens_teratur' => 'ya',
            'mens_lama' => '6',
            'mens_jarak_siklus' => '28',
            'makanan_dikonsumsi' => 'Nasi, sayur, telur',
        ];
        foreach (['pagi', 'jam_10', 'siang', 'jam_4', 'malam'] as $mealTime) {
            $input['makanan_' . $mealTime] = 'Nasi dan lauk';
            $input['jumlah_' . $mealTime] = '1 porsi';
        }
        for ($index = 1; $index <= 6; $index++) {
            $input['makan_' . $index] = 'selalu';
        }
        return $input;
    }
}
