<?php

declare(strict_types=1);

require_once __DIR__ . '/SuperadminOperationalService.php';
require_once dirname(__DIR__, 2) . '/config/validation.php';

final class SuperadminEducationService extends SuperadminOperationalService
{
    public function correctArticle(
        ActorContext $actor,
        int $id,
        mixed $title,
        mixed $content,
        string $reason,
        string $requestId
    ): void {
        $this->assertActor($actor);
        $title = normalizeText($title, 255);
        $content = normalizeText($content, 50000);
        if ($title === '' || $content === '') {
            throw new InvalidArgumentException('Judul dan konten wajib diisi.');
        }
        $this->correctRecord($actor, 'artikel_edukasi', $id, [
            'judul' => $title,
            'konten' => $content,
        ], $reason, $requestId);
    }

    public function correctAdvice(
        ActorContext $actor,
        int $id,
        array $input,
        string $reason,
        string $requestId
    ): void {
        $this->assertActor($actor);
        $values = [];
        foreach ([
            'judul_saran' => 100,
            'isi_saran' => 10000,
            'rekomendasi_makanan' => 10000,
            'kapan_rujuk_ke_ahli' => 10000,
        ] as $field => $max) {
            $values[$field] = normalizeText($input[$field] ?? '', $max);
        }
        if ($values['judul_saran'] === '' || $values['isi_saran'] === ''
            || $values['rekomendasi_makanan'] === '') {
            throw new InvalidArgumentException('Saran edukasi tidak lengkap.');
        }
        $this->correctRecord(
            $actor, 'saran_edukasi', $id, $values, $reason, $requestId
        );
    }

    public function archive(
        ActorContext $actor,
        string $table,
        int $id,
        string $reason,
        string $requestId
    ): void {
        if (!in_array($table, ['artikel_edukasi', 'saran_edukasi'], true)) {
            throw new InvalidArgumentException('Jenis edukasi tidak valid.');
        }
        $this->archiveRecord($actor, $table, $id, $reason, $requestId);
    }
}
