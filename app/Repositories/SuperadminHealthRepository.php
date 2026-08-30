<?php

declare(strict_types=1);

final class SuperadminHealthRepository
{
    private const TYPES = [
        'questionnaire' => [
            'table' => 'kuesioner',
            'date' => 'r.created_at',
            'summary' => "('Gejala: ' || r.skor_gejala || ' · Hb: ' || COALESCE(r.kadar_hb, '-'))",
            'mysql_summary' => "CONCAT('Gejala: ', r.skor_gejala, ' · Hb: ', COALESCE(r.kadar_hb, '-'))",
        ],
        'risk' => [
            'table' => 'hasil_deteksi',
            'date' => 'r.tanggal',
            'summary' => 'r.kategori_risiko',
            'mysql_summary' => 'r.kategori_risiko',
        ],
        'hb' => [
            'table' => 'kadar_hb',
            'date' => 'r.tanggal_periksa',
            'summary' => "CAST(r.nilai_hb AS TEXT) || ' · ' || r.kategori_anemia",
            'mysql_summary' => "CONCAT(r.nilai_hb, ' · ', r.kategori_anemia)",
        ],
        'ttd' => [
            'table' => 'konsumsi_ttd',
            'date' => 'r.tanggal',
            'summary' => 'r.status_konsumsi',
            'mysql_summary' => 'r.status_konsumsi',
        ],
        'menstruation' => [
            'table' => 'riwayat_haid',
            'date' => 'r.tanggal_mulai',
            'summary' => "r.tanggal_mulai || ' — ' || COALESCE(r.tanggal_selesai, 'aktif')",
            'mysql_summary' => "CONCAT(r.tanggal_mulai, ' — ', COALESCE(r.tanggal_selesai, 'aktif'))",
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
            throw new InvalidArgumentException('Jenis data tidak valid.');
        }
        $search = trim($search);
        if (mb_strlen($search) > 100) {
            throw new InvalidArgumentException('Pencarian terlalu panjang.');
        }
        $definition = self::TYPES[$type];
        $table = $definition['table'];
        $where = $archived ? 'r.archived_at IS NOT NULL' : 'r.archived_at IS NULL';
        $questionnaireJoin = '';
        if (!$archived && $type === 'questionnaire') {
            $where .= ' AND r.history_only_at IS NULL';
        }
        if (!$archived && $type === 'risk') {
            $questionnaireJoin = ' JOIN kuesioner q ON q.id = r.questionnaire_id';
            $where .= ' AND q.archived_at IS NULL AND q.history_only_at IS NULL';
        }
        $parameters = [];
        if ($search !== '') {
            $where .= ' AND (u.nama LIKE ? OR u.username LIKE ?)';
            $parameters = ['%' . $search . '%', '%' . $search . '%'];
        }
        $from = " FROM {$table} r
            JOIN users u ON u.id = r.user_id AND u.role = 'siswa'
            {$questionnaireJoin}
            WHERE {$where}";
        $count = $this->pdo->prepare('SELECT COUNT(*)' . $from);
        $count->execute($parameters);
        $total = (int) $count->fetchColumn();
        $perPage = max(1, min(100, $perPage));
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $summary = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
            ? $definition['summary'] : $definition['mysql_summary'];
        $statement = $this->pdo->prepare(
            "SELECT r.id, r.user_id, u.nama student_name,
                u.username student_username, {$definition['date']} record_date,
                {$summary} summary, r.corrected_at, r.archived_at"
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
