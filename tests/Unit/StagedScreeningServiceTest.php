<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class StagedScreeningServiceTest extends TestCase
{
    public function testSymptomSubmissionPersistsOnlyProfileAndSymptoms(): void
    {
        $store = new InMemoryStagedScreeningStore();
        $service = new StagedScreeningService($store);

        $result = $service->submitSymptoms(17, $this->symptoms(5));

        self::assertSame(101, $result['questionnaire_id']);
        self::assertSame(5.0, $result['symptom_average']);
        self::assertTrue($result['risk_eligible']);
        self::assertSame('faktor_risiko_tersedia', $store->created['stage']);
        self::assertStringContainsString('Sahabat merasakan cepat lelah', $store->created['snapshot']);
        self::assertStringNotContainsString('sikap', $store->created['snapshot']);
        self::assertStringNotContainsString('pengetahuan', $store->created['snapshot']);
    }

    public function testLowSymptomAverageIsFinalAndCannotBeEscalatedByForgedPost(): void
    {
        $store = new InMemoryStagedScreeningStore();
        $service = new StagedScreeningService($store);
        $initial = $service->submitSymptoms(17, $this->symptoms(4));
        $store->eligible = [
            'id' => $initial['questionnaire_id'],
            'rerata_gejala' => 4.0,
            'tahap_screening' => 'gejala_selesai',
            'answers_snapshot' => $store->created['snapshot'],
        ];

        $this->expectException(InvalidArgumentException::class);
        $service->submitRiskFactors(17, $initial['questionnaire_id'], $this->riskFactors());
    }

    public function testEligibleStudentCanCompleteRiskFactorsAndReceiveIndication(): void
    {
        $store = new InMemoryStagedScreeningStore();
        $service = new StagedScreeningService($store);
        $initial = $service->submitSymptoms(17, $this->symptoms(5));
        $store->eligible = [
            'id' => $initial['questionnaire_id'],
            'rerata_gejala' => 5.0,
            'tahap_screening' => 'faktor_risiko_tersedia',
            'answers_snapshot' => $store->created['snapshot'],
        ];
        $riskInput = $this->riskFactors();
        $riskInput['mens_teratur'] = 'tidak';

        $result = $service->submitRiskFactors(17, $initial['questionnaire_id'], $riskInput);

        self::assertSame(50.0, $result['risk_factor_percentage']);
        self::assertTrue($result['anemia_indicated']);
        self::assertSame('terindikasi_anemia', $store->completed['outcome']);
        self::assertStringContainsString('Pola makan sehari-hari', $store->completed['snapshot']);
    }

    public function testPendingRiskStageCanBeResumedForItsOwner(): void
    {
        $store = new InMemoryStagedScreeningStore();
        $store->latestEligible = [
            'id' => 88,
            'rerata_gejala' => 6.2,
            'tahap_screening' => 'faktor_risiko_tersedia',
        ];

        $pending = (new StagedScreeningService($store))->pendingRiskFactors(17);

        self::assertSame(88, $pending['id']);
    }

    public function testLatestOwnedResultCanBeLoadedWithoutGuessingAnId(): void
    {
        $store = new InMemoryStagedScreeningStore();
        $store->latestResult = ['id' => 77, 'tahap_screening' => 'selesai'];

        $result = (new StagedScreeningService($store))->latestResultForStudent(17);

        self::assertSame(77, $result['id']);
    }

    public function testMenstruationIsNotAppliedToMaleStudent(): void
    {
        $store = new InMemoryStagedScreeningStore();
        $service = new StagedScreeningService($store);
        $initial = $service->submitSymptoms(17, [
            ...$this->symptoms(5),
            'jenis_kelamin' => 'laki_laki',
        ]);
        $store->eligible = [
            'id' => $initial['questionnaire_id'],
            'rerata_gejala' => 5.0,
            'tahap_screening' => 'faktor_risiko_tersedia',
            'jenis_kelamin' => 'laki_laki',
            'answers_snapshot' => $store->created['snapshot'],
        ];
        $risk = $this->riskFactors();
        unset($risk['mens_sudah'], $risk['mens_usia_th'], $risk['mens_usia_bln'], $risk['mens_teratur'], $risk['mens_lama'], $risk['mens_jarak_siklus']);

        $result = $service->submitRiskFactors(17, $initial['questionnaire_id'], $risk);

        self::assertSame(100.0, $result['risk_factor_percentage']);
        self::assertStringContainsString('Tidak berlaku', $store->completed['snapshot']);
    }

    /** @return array<string, mixed> */
    private function symptoms(int $value): array
    {
        $input = [
            'tanggal_lahir' => (new DateTimeImmutable('today'))->modify('-15 years')->format('Y-m-d'),
            'pendidikan' => 'Kelas IX',
            'jenis_kelamin' => 'perempuan',
        ];
        for ($index = 1; $index <= 10; $index++) {
            $input['gejala_' . $index] = (string) $value;
        }
        return $input;
    }

    /** @return array<string, mixed> */
    private function riskFactors(): array
    {
        $input = [
            'mens_sudah' => 'ya',
            'mens_usia_th' => '12',
            'mens_usia_bln' => '0',
            'mens_teratur' => 'ya',
            'mens_lama' => '6',
            'mens_jarak_siklus' => '28',
            'makanan_dikonsumsi' => 'Nasi, sayur, telur',
        ];
        foreach (['pagi', 'jam_10', 'siang', 'jam_4', 'malam'] as $mealTime) {
            $input['makanan_' . $mealTime] = 'Nasi dan lauk';
            $input['jumlah_' . $mealTime] = '1 porsi';
        }
        for ($index = 1; $index <= 6; $index++) {
            $input['makan_' . $index] = 'selalu';
        }
        return $input;
    }
}

final class InMemoryStagedScreeningStore implements StagedScreeningStore
{
    /** @var array<string, mixed> */
    public array $created = [];
    /** @var array<string, mixed> */
    public array $completed = [];
    /** @var array<string, mixed>|null */
    public ?array $eligible = null;
    /** @var array<string, mixed>|null */
    public ?array $latestEligible = null;
    /** @var array<string, mixed>|null */
    public ?array $latestResult = null;

    public function createSymptomScreening(
        int $userId,
        array $values,
        array $score,
        string $snapshot
    ): int {
        $this->created = [
            'user_id' => $userId,
            'values' => $values,
            'score' => $score,
            'stage' => $score['risk_eligible'] ? 'faktor_risiko_tersedia' : 'gejala_selesai',
            'snapshot' => $snapshot,
        ];
        return 101;
    }

    public function findRiskEligible(int $userId, int $questionnaireId): ?array
    {
        return $this->eligible;
    }

    public function completeRiskFactorScreening(
        int $userId,
        int $questionnaireId,
        array $values,
        array $score,
        string $snapshot
    ): void {
        $this->completed = [
            'user_id' => $userId,
            'questionnaire_id' => $questionnaireId,
            'values' => $values,
            'score' => $score,
            'outcome' => $score['anemia_indicated'] ? 'terindikasi_anemia' : 'tidak_terindikasi_anemia',
            'snapshot' => $snapshot,
        ];
    }

    public function findForStudent(int $userId, int $questionnaireId): ?array
    {
        return null;
    }

    public function findLatestRiskEligible(int $userId): ?array
    {
        return $this->latestEligible;
    }

    public function findLatestForStudent(int $userId): ?array
    {
        return $this->latestResult;
    }
}
