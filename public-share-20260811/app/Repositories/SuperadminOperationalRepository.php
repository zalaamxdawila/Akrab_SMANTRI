<?php

declare(strict_types=1);

final class SuperadminOperationalRepository
{
    private const TYPES = [
        'consultation' => [
            'from' => "konsultasi r JOIN users u ON u.id = r.siswa_id",
            'date' => 'r.tanggal_kirim',
            'title' => 'u.nama',
            'summary' => 'r.pertanyaan',
        ],
        'reply' => [
            'from' => "balasan_konsultasi r
                JOIN konsultasi k ON k.id = r.konsultasi_id
                JOIN users u ON u.id = k.siswa_id",
            'date' => 'r.tanggal_balas',
            'title' => 'u.nama',
            'summary' => 'r.isi_balasan',
        ],
        'article' => [
            'from' => "artikel_edukasi r JOIN users u ON u.id = r.uks_id",
            'date' => 'r.tanggal_publikasi',
            'title' => 'r.judul',
            'summary' => 'r.konten',
        ],
        'advice' => [
            'from' => 'saran_edukasi r',
            'date' => 'r.id',
            'title' => 'r.judul_saran',
            'summary' => 'r.isi_saran',
        ],
        'schedule' => [
            'from' => "jadwal_notifikasi r JOIN users u ON u.id = r.siswa_id",
            'date' => 'r.id',
            'title' => 'u.nama',
            'summary' => 'r.hari',
        ],
        'delivery' => [
            'from' => "log_notifikasi r JOIN users u ON u.id = r.siswa_id",
            'date' => 'r.tanggal_kirim',
            'title' => 'u.nama',
            'summary' => 'r.status_terkirim',
        ],
    ];

    public function __construct(private PDO $pdo)
    {
    }

    public function paginate(
        string $type,
        string $search,
        bool $archived,
        int $page,
        int $perPage
    ): array {
        if (!isset(self::TYPES[$type])) {
            throw new InvalidArgumentException('Jenis operasional tidak valid.');
        }
        $search = trim($search);
        if (mb_strlen($search) > 100) {
            throw new InvalidArgumentException('Pencarian terlalu panjang.');
        }
        $definition = self::TYPES[$type];
        $where = $archived ? 'r.archived_at IS NOT NULL' : 'r.archived_at IS NULL';
        $parameters = [];
        if ($search !== '') {
            $where .= " AND ({$definition['title']} LIKE ?
                OR {$definition['summary']} LIKE ?)";
            $parameters = ['%' . $search . '%', '%' . $search . '%'];
        }
        $from = " FROM {$definition['from']} WHERE {$where}";
        $count = $this->pdo->prepare('SELECT COUNT(*)' . $from);
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();
        $perPage = max(1, min(100, $perPage));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $statement = $this->pdo->prepare(
            "SELECT r.id, {$definition['date']} record_date,
                {$definition['title']} title, {$definition['summary']} summary,
                r.corrected_at, r.archived_at"
            . $from
            . " ORDER BY {$definition['date']} DESC, r.id DESC LIMIT ? OFFSET ?"
        );
        $position = 1;
        foreach ($parameters as $parameter) {
            $statement->bindValue($position++, $parameter);
        }
        $statement->bindValue($position++, $perPage, PDO::PARAM_INT);
        $statement->bindValue($position, ($page - 1) * $perPage, PDO::PARAM_INT);
        $statement->execute();
        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }
}
