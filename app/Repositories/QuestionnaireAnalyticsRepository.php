<?php

declare(strict_types=1);

final class QuestionnaireAnalyticsRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /**
     * @return array{
     *   total_responses:int,total_students:int,responding_students:int,
     *   not_responded_students:int,lab_available:int,legacy_responses:int,
     *   staged:array{responses:int,completed:int,indicated:int,avg_symptom:float,avg_risk:float},
     *   averages:array{gejala:float,makan:float,pengetahuan:float,sikap:float}
     * }
     */
    public function aggregate(): array
    {
        $row = $this->pdo->query(
            "SELECT COUNT(*) total_responses,
                    SUM(CASE WHEN kadar_hb IS NOT NULL
                        AND kadar_mchc IS NOT NULL
                        AND kadar_mcv IS NOT NULL
                        AND kadar_mch IS NOT NULL THEN 1 ELSE 0 END) lab_available,
                    SUM(CASE WHEN COALESCE(versi_screening, '') = '' THEN 1 ELSE 0 END) legacy_responses,
                    SUM(CASE WHEN COALESCE(versi_screening, '') <> '' THEN 1 ELSE 0 END) staged_responses,
                    SUM(CASE WHEN COALESCE(versi_screening, '') <> ''
                        AND tahap_screening = 'selesai' THEN 1 ELSE 0 END) staged_completed,
                    SUM(CASE WHEN COALESCE(versi_screening, '') <> ''
                        AND hasil_screening = 'terindikasi_anemia' THEN 1 ELSE 0 END) staged_indicated,
                    AVG(CASE WHEN COALESCE(versi_screening, '') = '' THEN skor_gejala END) avg_gejala,
                    AVG(CASE WHEN COALESCE(versi_screening, '') = '' THEN skor_makan END) avg_makan,
                    AVG(CASE WHEN COALESCE(versi_screening, '') = '' THEN skor_pengetahuan END) avg_pengetahuan,
                    AVG(CASE WHEN COALESCE(versi_screening, '') = '' THEN skor_sikap END) avg_sikap,
                    AVG(CASE WHEN COALESCE(versi_screening, '') <> '' THEN rerata_gejala END) staged_avg_symptom,
                    AVG(CASE WHEN COALESCE(versi_screening, '') <> ''
                        AND tahap_screening = 'selesai' THEN persentase_faktor_risiko END) staged_avg_risk
             FROM kuesioner
             WHERE archived_at IS NULL AND history_only_at IS NULL"
        )->fetch() ?: [];
        $totalStudents = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM users WHERE role = 'siswa' AND status = 'active'"
        )->fetchColumn();
        $respondingStudents = (int) $this->pdo->query(
            "SELECT COUNT(DISTINCT k.user_id)
             FROM kuesioner k
             JOIN users u ON u.id = k.user_id
                AND u.role = 'siswa' AND u.status = 'active'
             WHERE k.archived_at IS NULL
               AND k.history_only_at IS NULL"
        )->fetchColumn();

        return [
            'total_responses' => (int) ($row['total_responses'] ?? 0),
            'total_students' => $totalStudents,
            'responding_students' => $respondingStudents,
            'not_responded_students' => max(
                0,
                $totalStudents - $respondingStudents
            ),
            'lab_available' => (int) ($row['lab_available'] ?? 0),
            'legacy_responses' => (int) ($row['legacy_responses'] ?? 0),
            'staged' => [
                'responses' => (int) ($row['staged_responses'] ?? 0),
                'completed' => (int) ($row['staged_completed'] ?? 0),
                'indicated' => (int) ($row['staged_indicated'] ?? 0),
                'avg_symptom' => round((float) ($row['staged_avg_symptom'] ?? 0), 1),
                'avg_risk' => round((float) ($row['staged_avg_risk'] ?? 0), 1),
            ],
            'averages' => [
                'gejala' => round((float) ($row['avg_gejala'] ?? 0), 1),
                'makan' => round((float) ($row['avg_makan'] ?? 0), 1),
                'pengetahuan' => round((float) ($row['avg_pengetahuan'] ?? 0), 1),
                'sikap' => round((float) ($row['avg_sikap'] ?? 0), 1),
            ],
        ];
    }

    /** @return list<string> */
    public function activeAnswerSnapshots(): array
    {
        return $this->pdo->query(
            "SELECT k.answers_snapshot
             FROM kuesioner k
             JOIN users u ON u.id = k.user_id
                AND u.role = 'siswa' AND u.status = 'active'
             WHERE k.archived_at IS NULL
               AND k.history_only_at IS NULL
               AND COALESCE(k.versi_screening, '') = ''
               AND k.answers_snapshot IS NOT NULL
               AND k.answers_snapshot <> ''
             ORDER BY k.created_at, k.id"
        )->fetchAll(PDO::FETCH_COLUMN);
    }

    /** @return list<array<string, mixed>> */
    public function latestStagedByStudent(): array
    {
        return $this->latestStudentRows(false, true);
    }

    /** @return list<array<string, mixed>> */
    public function latestLegacyByStudent(): array
    {
        return $this->latestStudentRows(false, false);
    }

    /** @return list<array<string, mixed>> */
    public function latestStagedByStudentForExport(): array
    {
        return $this->latestStudentRows(false, true);
    }

    /** @return list<array<string, mixed>> */
    public function latestLegacyByStudentForExport(): array
    {
        return $this->latestStudentRows(modelExecutionGatePassed(), false);
    }

    /** @return list<array<string, mixed>> */
    private function latestStudentRows(bool $includeClinicalRisk, bool $staged): array
    {
        $versionCondition = $staged ? "<> ''" : "= ''";
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
                    k.skor_pengetahuan, k.skor_sikap, k.answers_snapshot,
                    k.tanggal_lahir, k.jenis_kelamin, k.pendidikan, k.created_at,
                    k.tahap_screening, k.rerata_gejala, k.persentase_faktor_risiko,
                    k.hasil_screening, k.versi_screening,
                    {$riskColumns}
             FROM users u
             JOIN kuesioner k ON k.id = (
                SELECT k2.id
                FROM kuesioner k2
                WHERE k2.user_id = u.id AND k2.archived_at IS NULL
                  AND k2.history_only_at IS NULL
                  AND COALESCE(k2.versi_screening, '') {$versionCondition}
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
    public function latestPrimaryForStudent(int $studentId): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT *
             FROM kuesioner
             WHERE user_id = ? AND archived_at IS NULL
               AND history_only_at IS NULL
             ORDER BY created_at DESC, id DESC
             LIMIT 1'
        );
        $statement->execute([$studentId]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    /** @return array<string, mixed>|null */
    public function primaryQuestionnaireForStudent(int $studentId, int $questionnaireId): ?array
    {
        if ($studentId <= 0 || $questionnaireId <= 0) {
            return null;
        }

        $statement = $this->pdo->prepare(
            'SELECT *
             FROM kuesioner
             WHERE id = ? AND user_id = ? AND archived_at IS NULL
               AND history_only_at IS NULL
             LIMIT 1'
        );
        $statement->execute([$questionnaireId, $studentId]);
        $row = $statement->fetch();
        return $row ?: null;
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
        if ($questionnaireId <= 0 || !modelExecutionGatePassed()) {
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
