<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ImpersonationRouteCoverageTest extends TestCase
{
    public function testAllProtectedHtmlRoutesHaveBanner(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ([
            'uks/dashboard.php', 'uks/data_siswa.php', 'uks/detail_siswa.php',
            'uks/scan_qr.php', 'uks/cetak_laporan_eksekutif.php',
            'uks/jawab_konsultasi.php', 'uks/kelola_artikel.php',
            'uks/kelola_tautan.php', 'uks/edukasi.php',
            'uks/cetak_rujukan.php', 'uks/import_siswa.php', 'uks/profil.php',
            'orangtua/dashboard.php',
        ] as $route) {
            self::assertStringContainsString(
                'renderImpersonationBanner',
                file_get_contents($root . '/' . $route),
                $route
            );
        }
    }

    public function testAllowedMutationMatrixIsExplicit(): void
    {
        self::assertSame(
            'questionnaire.submit',
            impersonationActionForRequest('/siswa/kuesioner.php', [])
        );
        self::assertSame(
            'consultation.reply',
            impersonationActionForRequest('/uks/jawab_konsultasi.php', [])
        );
        self::assertSame(
            'parent_link.request',
            impersonationActionForRequest('/orangtua/dashboard.php', [])
        );
    }
}
