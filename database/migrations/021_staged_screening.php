<?php

declare(strict_types=1);

return [
    'version' => '021_staged_screening',
    'description' => 'Add nullable fields for symptom-gated school screening',
    'up' => function (PDO $pdo): void {
        $columns = [
            'jenis_kelamin' => "jenis_kelamin ENUM('perempuan', 'laki_laki') NULL AFTER tanggal_lahir",
            'tahap_screening' => "tahap_screening ENUM('gejala_selesai', 'faktor_risiko_tersedia', 'selesai') NULL AFTER answers_snapshot",
            'rerata_gejala' => 'rerata_gejala DECIMAL(4,1) NULL AFTER tahap_screening',
            'persentase_faktor_risiko' => 'persentase_faktor_risiko DECIMAL(5,1) NULL AFTER rerata_gejala',
            'hasil_screening' => "hasil_screening ENUM('gejala_di_bawah_ambang', 'terindikasi_anemia', 'tidak_terindikasi_anemia') NULL AFTER persentase_faktor_risiko",
            'versi_screening' => 'versi_screening VARCHAR(80) NULL AFTER hasil_screening',
        ];

        foreach ($columns as $name => $definition) {
            $column = $pdo->query(
                'SHOW COLUMNS FROM kuesioner LIKE ' . $pdo->quote($name)
            );
            if (!$column->fetch()) {
                $pdo->exec('ALTER TABLE kuesioner ADD COLUMN ' . $definition);
            }
        }

        $index = $pdo->query(
            'SHOW INDEX FROM kuesioner WHERE Key_name = '
            . $pdo->quote('idx_kuesioner_screening_stage')
        );
        if (!$index->fetch()) {
            $pdo->exec(
                'ALTER TABLE kuesioner ADD INDEX idx_kuesioner_screening_stage '
                . '(user_id, tahap_screening, archived_at, created_at)'
            );
        }
    },
    'down' => function (PDO $pdo): void {
        $pdo->exec(
            'ALTER TABLE kuesioner '
            . 'DROP INDEX idx_kuesioner_screening_stage, '
            . 'DROP COLUMN versi_screening, '
            . 'DROP COLUMN hasil_screening, '
            . 'DROP COLUMN persentase_faktor_risiko, '
            . 'DROP COLUMN rerata_gejala, '
            . 'DROP COLUMN tahap_screening, '
            . 'DROP COLUMN jenis_kelamin'
        );
    },
];
