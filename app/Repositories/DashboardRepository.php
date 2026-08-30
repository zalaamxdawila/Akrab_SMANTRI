<?php

declare(strict_types=1);

final class DashboardRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array{total_students:int, high_risk:int, pending_consultations:int, risk_distribution:array<string,int>} */
    public function uksSummary(): array
    {
        $total = (int) $this->pdo->query("SELECT COUNT(*) FROM users WHERE role = 'siswa'")->fetchColumn();
        $highRisk = (int) $this->pdo->query(
            "SELECT COUNT(*)
             FROM hasil_deteksi h
             JOIN kuesioner q ON q.id = h.questionnaire_id
                AND q.archived_at IS NULL AND q.history_only_at IS NULL
             WHERE h.kategori_risiko = 'tinggi' AND h.archived_at IS NULL
               AND NOT EXISTS (
                   SELECT 1 FROM hasil_deteksi newer
                   JOIN kuesioner newer_q ON newer_q.id = newer.questionnaire_id
                      AND newer_q.archived_at IS NULL
                      AND newer_q.history_only_at IS NULL
                   WHERE newer.user_id = h.user_id
                     AND newer.archived_at IS NULL
                     AND (newer.tanggal > h.tanggal
                          OR (newer.tanggal = h.tanggal AND newer.id > h.id))
               )"
        )->fetchColumn();
        $pending = (int) $this->pdo->query("SELECT COUNT(*) FROM konsultasi WHERE status = 'menunggu'")->fetchColumn();
        $distribution = ['tinggi' => 0, 'sedang' => 0, 'rendah' => 0];
        $rows = $this->pdo->query(
            "SELECT h.kategori_risiko, COUNT(DISTINCT h.user_id) AS total
             FROM hasil_deteksi h
             JOIN kuesioner q ON q.id = h.questionnaire_id
                AND q.archived_at IS NULL AND q.history_only_at IS NULL
             WHERE h.archived_at IS NULL
               AND NOT EXISTS (
                   SELECT 1 FROM hasil_deteksi newer
                   JOIN kuesioner newer_q ON newer_q.id = newer.questionnaire_id
                      AND newer_q.archived_at IS NULL
                      AND newer_q.history_only_at IS NULL
                   WHERE newer.user_id = h.user_id
                     AND newer.archived_at IS NULL
                     AND (newer.tanggal > h.tanggal
                          OR (newer.tanggal = h.tanggal AND newer.id > h.id))
               )
             GROUP BY h.kategori_risiko"
        )->fetchAll();
        foreach ($rows as $row) {
            if (array_key_exists($row['kategori_risiko'], $distribution)) {
                $distribution[$row['kategori_risiko']] = (int) $row['total'];
            }
        }

        return ['total_students' => $total, 'high_risk' => $highRisk, 'pending_consultations' => $pending, 'risk_distribution' => $distribution];
    }

    /** @return array{labels:list<string>, compliant:list<int>, non_compliant:list<int>} */
    public function ttdComplianceLastSevenDays(int $totalStudents): array
    {
        $labels = $compliant = $nonCompliant = [];
        $statement = $this->pdo->prepare("SELECT COUNT(*) FROM konsumsi_ttd WHERE tanggal = ? AND status_konsumsi = 'sudah' AND archived_at IS NULL");
        for ($offset = 6; $offset >= 0; $offset--) {
            $date = date('Y-m-d', strtotime("-$offset days"));
            $labels[] = date('d M', strtotime($date));
            $statement->execute([$date]);
            $count = (int) $statement->fetchColumn();
            $compliant[] = $count;
            $nonCompliant[] = max(0, $totalStudents - $count);
        }
        return ['labels' => $labels, 'compliant' => $compliant, 'non_compliant' => $nonCompliant];
    }
}
