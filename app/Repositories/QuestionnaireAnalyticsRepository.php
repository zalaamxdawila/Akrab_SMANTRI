<?php

declare(strict_types=1);

final class QuestionnaireAnalyticsRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array{
     *   total_responses:int,responding_students:int,lab_available:int,
     *   averages:array{gejala:float,makan:float,pengetahuan:float,sikap:float}
     * }
     */
    public function aggregate(): array
    {
        $row = $this->pdo->query(
            'SELECT COUNT(*) total_responses,
                    COUNT(DISTINCT user_id) responding_students,
                    SUM(CASE WHEN kadar_hb IS NOT NULL
                        AND kadar_mchc IS NOT NULL
                        AND kadar_mcv IS NOT NULL
                        AND kadar_mch IS NOT NULL THEN 1 ELSE 0 END) lab_available,
                    AVG(skor_gejala) avg_gejala,
                    AVG(skor_makan) avg_makan,
                    AVG(skor_pengetahuan) avg_pengetahuan,
                    AVG(skor_sikap) avg_sikap
             FROM kuesioner
             WHERE archived_at IS NULL'
        )->fetch() ?: [];

        return [
            'total_responses' => (int) ($row['total_responses'] ?? 0),
            'responding_students' => (int) ($row['responding_students'] ?? 0),
            'lab_available' => (int) ($row['lab_available'] ?? 0),
            'averages' => [
                'gejala' => round((float) ($row['avg_gejala'] ?? 0), 1),
                'makan' => round((float) ($row['avg_makan'] ?? 0), 1),
                'pengetahuan' => round((float) ($row['avg_pengetahuan'] ?? 0), 1),
                'sikap' => round((float) ($row['avg_sikap'] ?? 0), 1),
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    public function latestByStudent(): array
    {
        return $this->latestStudentRows(false);
    }

    /** @return list<array<string, mixed>> */
    public function latestByStudentForExport(): array
    {
        return $this->latestStudentRows(clinicalApprovalGatePassed());
    }

    /** @return list<array<string, mixed>> */
    private function latestStudentRows(bool $includeClinicalRisk): array
    {
        $riskColumns = 'NULL probabilitas_risiko, NULL kategori_risiko';
        $riskJoin = '';
        if ($includeClinicalRisk) {
            $riskColumns = 'hd.probabilitas_risiko, hd.kategori_risiko';
            $riskJoin = "LEFT JOIN hasil_deteksi hd ON hd.id = (
                SELECT hd2.id
                FROM hasil_deteksi hd2
                WHERE hd2.user_id = u.id
                  AND hd2.questionnaire_id = k.id
                  AND hd2.archived_at IS NULL
                ORDER BY hd2.created_at DESC, hd2.tanggal DESC, hd2.id DESC
                LIMIT 1
             )";
        }

        return $this->pdo->query(
            "SELECT u.id student_id, u.nama, u.username, u.kelas,
                    k.id questionnaire_id, k.kadar_hb, k.kadar_mchc,
                    k.kadar_mcv, k.kadar_mch, k.skor_gejala, k.skor_makan,
                    k.skor_pengetahuan, k.skor_sikap, k.created_at,
                    {$riskColumns}
             FROM users u
             JOIN kuesioner k ON k.id = (
                SELECT k2.id
                FROM kuesioner k2
                WHERE k2.user_id = u.id AND k2.archived_at IS NULL
                ORDER BY k2.created_at DESC, k2.id DESC
                LIMIT 1
             )
             {$riskJoin}
             WHERE u.role = 'siswa' AND u.status = 'active'
             ORDER BY u.nama, u.id"
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function historyForStudent(int $studentId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT *
             FROM kuesioner
             WHERE user_id = ? AND archived_at IS NULL
             ORDER BY created_at, id'
        );
        $statement->execute([$studentId]);
        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function student(int $studentId): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT id, nama, username, kelas
             FROM users
             WHERE id = ? AND role = 'siswa' AND status = 'active'
             LIMIT 1"
        );
        $statement->execute([$studentId]);
        $student = $statement->fetch();
        return $student ?: null;
    }

    /** @return array<string, mixed>|null */
    public function approvedStudentForParent(int $parentId): ?array
    {
        $statement = $this->pdo->prepare(
            "SELECT u.id, u.nama, u.username, u.kelas
             FROM parent_student_links psl
             JOIN users u ON u.id = psl.student_id
                AND u.role = 'siswa' AND u.status = 'active'
             WHERE psl.parent_id = ?
               AND psl.status = 'approved'
               AND psl.archived_at IS NULL
             LIMIT 1"
        );
        $statement->execute([$parentId]);
        $student = $statement->fetch();
        return $student ?: null;
    }

    /** @return array<string, mixed>|null */
    public function latestDetectionForStudent(int $studentId, int $questionnaireId): ?array
    {
        if ($questionnaireId <= 0 || !clinicalApprovalGatePassed()) {
            return null;
        }

        $sql = 'SELECT *
             FROM hasil_deteksi
             WHERE user_id = ? AND questionnaire_id = ?
               AND archived_at IS NULL';
        $parameters = [$studentId, $questionnaireId];
        $sql .= ' ORDER BY created_at DESC, tanggal DESC, id DESC LIMIT 1';

        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);
        $detection = $statement->fetch();
        return $detection ?: null;
    }
}
