<?php

use PHPUnit\Framework\TestCase;

final class ErrorHandlingTest extends TestCase
{
    public function testPublicErrorMessageIsGeneric(): void
    {
        self::assertSame(
            'Terjadi gangguan pada aplikasi. Silakan coba kembali atau hubungi administrator.',
            publicErrorMessage()
        );
    }

    public function testProductionDisablesDisplayedErrors(): void
    {
        self::assertSame(0, productionDisplayErrorsValue());
    }

    public function testCorrelationIdIsStableAndOpaque(): void
    {
        $first = requestCorrelationId();
        $second = requestCorrelationId();

        self::assertSame($first, $second);
        self::assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $first);
    }
}
