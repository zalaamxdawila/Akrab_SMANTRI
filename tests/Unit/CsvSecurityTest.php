<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class CsvSecurityTest extends TestCase
{
    public function testFormulaCellsAreNeutralized(): void
    {
        self::assertSame("'=1+1", csvSafeCell('=1+1'));
        self::assertSame("'+cmd", csvSafeCell('+cmd'));
        self::assertSame('normal', csvSafeCell('normal'));
    }

    public function testHeaderAndRowsRequireExactContract(): void
    {
        self::assertTrue(csvHeaderIsValid(['Nama', ' Kelas ', 'Username', 'Password']));
        self::assertFalse(csvHeaderIsValid(['Nama', 'Kelas', 'Username']));
        self::assertSame('Budi', csvStudentRow(['Budi', 'X', 'budi01', 'password']) [0]);
        $this->expectException(InvalidArgumentException::class);
        csvStudentRow(['Budi', 'X', 'bad username', 'password']);
    }

    public function testImportAndExportContainRequiredHardeningHooks(): void
    {
        $import = file_get_contents(dirname(__DIR__, 2) . '/uks/import_siswa.php');
        $export = file_get_contents(dirname(__DIR__, 2) . '/uks/export_csv.php');
        self::assertStringContainsString('AKRAB_CSV_MAX_BYTES', $import);
        self::assertStringContainsString('beginTransaction', $import);
        self::assertStringContainsString('batch_hash', $import);
        self::assertStringContainsString('csvSafeCell', $export);
    }
}
