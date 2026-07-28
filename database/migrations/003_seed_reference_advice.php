<?php

return [
    'version' => '003_seed_reference_advice',
    'description' => 'Insert required anemia education reference content',
    'up' => function (PDO $pdo): void {
        $records = [
            ['tidak_anemia', 'Kondisi Sehat', 'Pertahankan pola hidup sehat Anda saat ini.', 'Makan sayur, daging tanpa lemak, dan buah-buahan.', 'Tidak perlu rujukan.'],
            ['ringan', 'Anemia Ringan', 'Tingkatkan asupan zat besi dan pantau gejala bersama tenaga kesehatan.', 'Bayam, hati ayam, daging merah matang, dan makanan kaya vitamin C.', 'Hubungi petugas UKS jika pusing dan lemas memburuk.'],
            ['sedang', 'Anemia Sedang', 'Konsultasikan hasil pemeriksaan dengan petugas UKS atau tenaga kesehatan.', 'Daging merah matang, sayuran hijau gelap, dan sumber vitamin C.', 'Hubungi petugas UKS atau puskesmas untuk pemeriksaan lebih lanjut.'],
            ['berat', 'Perlu Pemeriksaan Segera', 'Hasil screening bukan diagnosis. Segera minta pemeriksaan tenaga kesehatan.', 'Ikuti rekomendasi tenaga kesehatan dan hindari pengobatan mandiri.', 'Segera hubungi puskesmas atau fasilitas kesehatan terdekat.'],
        ];

        $exists = $pdo->prepare('SELECT 1 FROM saran_edukasi WHERE kategori_anemia = ? LIMIT 1');
        $insert = $pdo->prepare(
            'INSERT INTO saran_edukasi
             (kategori_anemia, judul_saran, isi_saran, rekomendasi_makanan, kapan_rujuk_ke_ahli)
             VALUES (?, ?, ?, ?, ?)'
        );

        foreach ($records as $record) {
            $exists->execute([$record[0]]);
            if (!$exists->fetchColumn()) {
                $insert->execute($record);
            }
        }
    },
];
