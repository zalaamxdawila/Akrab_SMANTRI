<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SharedViewTest extends TestCase
{
    public function testConsultationPagesUseSharedFlashPartial(): void
    {
        foreach (['siswa/konsultasi.php', 'uks/jawab_konsultasi.php'] as $path) {
            $contents = file_get_contents(dirname(__DIR__, 2) . '/' . $path);
            self::assertStringContainsString("views/partials/flash.php", $contents);
        }
    }

    public function testFlashPartialEscapesMessages(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/views/partials/flash.php');
        self::assertStringContainsString('escape_output', $contents);
    }
}
