<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ValidationTest extends TestCase
{
    public function testDecimalAndIntegerBoundariesAreEnforced(): void
    {
        self::assertSame(12.5, optionalDecimal('12.5', 0, 30));
        self::assertNull(optionalDecimal('', 0, 30));
        self::assertSame(10, boundedInt('10', 0, 10));
        $this->expectException(InvalidArgumentException::class);
        boundedInt('11', 0, 10);
    }

    public function testQuestionnaireRejectsTamperedScoresAndEnums(): void
    {
        $payload = validQuestionnairePayload();
        $payload['gejala_1'] = '999';
        self::assertFalse(validateQuestionnaireInput($payload)['valid']);
        $payload = validQuestionnairePayload();
        $payload['makan_1'] = 'invalid';
        self::assertFalse(validateQuestionnaireInput($payload)['valid']);
    }

    public function testQuestionnaireCalculatesOnlyWhitelistedValues(): void
    {
        $payload = validQuestionnairePayload();
        $result = validateQuestionnaireInput($payload);
        self::assertTrue($result['valid']);
        self::assertSame(10, $result['values']['skor_gejala']);
        self::assertSame(6, $result['values']['skor_makan']);
    }

    public function testBmiRejectsImpossibleMeasurements(): void
    {
        self::assertTrue(validateBmiInput('50', '160')['valid']);
        self::assertFalse(validateBmiInput('0', '160')['valid']);
        self::assertFalse(validateBmiInput('50', '999')['valid']);
        self::assertFalse(validateBmiInput(['50'], '160')['valid']);
    }

    public function testNormalizationDoesNotDoubleEncodeStoredText(): void
    {
        self::assertSame('O\'Brien & Co', sanitize_input(" O'Brien & Co "));
        self::assertSame('O&#039;Brien &amp; Co', escape_output("O'Brien & Co"));
    }
}

function validQuestionnairePayload(): array
{
    $payload = [
        'mens_sudah' => 'ya', 'mens_teratur' => 'ya', 'pendidikan' => 'Kelas X',
        'makan_1' => 'tidak', 'makan_2' => 'tidak', 'makan_3' => 'tidak',
        'makan_4' => 'tidak', 'makan_5' => 'tidak', 'makan_6' => 'tidak',
    ];
    for ($i = 1; $i <= 10; $i++) {
        $payload['gejala_' . $i] = '1';
        $payload['sikap_' . $i] = '1';
        $payload['pengetahuan_' . $i] = ['a'];
    }
    return $payload;
}
