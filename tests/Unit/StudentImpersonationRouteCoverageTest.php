<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StudentImpersonationRouteCoverageTest extends TestCase
{
    public function testStudentHtmlRoutesRenderBannerAndExportsAreCentrallyBlocked(): void
    {
        $root = dirname(__DIR__, 2);
        foreach ([
            'dashboard.php', 'id_card.php', 'profil.php', 'kuesioner.php',
            'konsultasi.php', 'edukasi.php', 'baca_artikel.php',
            'hasil_deteksi.php', 'kalkulator_gizi.php', 'cetak_sertifikat.php',
        ] as $route) {
            self::assertStringContainsString(
                'renderImpersonationBanner',
                file_get_contents($root . '/siswa/' . $route),
                $route
            );
        }
        $config = file_get_contents($root . '/config.php');
        self::assertStringContainsString('export_calendar.php', $config);
        self::assertStringContainsString('export_csv.php', $config);
    }

    public function testCredentialAndMasterPostsFailClosedByRoutePolicy(): void
    {
        self::assertSame(
            'impersonation.route_unapproved',
            impersonationActionForRequest('/siswa/profil.php', ['update_password' => 1])
        );
        self::assertSame(
            'impersonation.route_unapproved',
            impersonationActionForRequest('/uks/import_siswa.php', [])
        );
        self::assertFalse(ImpersonationPolicy::allows('impersonation.route_unapproved'));
    }
}
