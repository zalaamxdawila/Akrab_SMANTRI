<?php

declare(strict_types=1);

final class StagedScreeningService
{
    public function __construct(
        private StagedScreeningStore $store,
        private StagedScreeningScore $score = new StagedScreeningScore(),
        private StagedScreeningSnapshot $snapshot = new StagedScreeningSnapshot()
    ) {
    }

    /** @return array{questionnaire_id:int,symptom_average:float,risk_eligible:bool} */
    public function submitSymptoms(int $userId, array $input): array
    {
        $validated = validateStagedSymptomInput($input);
        if (!$validated['valid']) {
            throw new InvalidArgumentException(implode(' ', $validated['errors']));
        }
        $score = $this->score->symptoms($validated['values']['symptoms']);
        $snapshot = $this->snapshot->symptoms($validated['values'], $score);
        $questionnaireId = $this->store->createSymptomScreening(
            $userId,
            $validated['values'],
            $score,
            $snapshot
        );

        return [
            'questionnaire_id' => $questionnaireId,
            'symptom_average' => $score['average'],
            'risk_eligible' => $score['risk_eligible'],
        ];
    }

    /** @return array{questionnaire_id:int,risk_factor_percentage:float,anemia_indicated:bool} */
    public function submitRiskFactors(int $userId, int $questionnaireId, array $input): array
    {
        if ($questionnaireId < 1) {
            throw new InvalidArgumentException('Kuesioner tidak valid.');
        }
        $screening = $this->store->findRiskEligible($userId, $questionnaireId);
        if (
            $screening === null
            || ($screening['tahap_screening'] ?? null) !== 'faktor_risiko_tersedia'
            || (float) ($screening['rerata_gejala'] ?? 0) <= StagedScreeningScore::SYMPTOM_THRESHOLD
        ) {
            throw new InvalidArgumentException('Tahap faktor risiko tidak tersedia untuk hasil gejala ini.');
        }

        if (($screening['jenis_kelamin'] ?? null) === 'laki_laki') {
            $input['mens_sudah'] = 'belum';
            unset(
                $input['mens_usia_th'],
                $input['mens_usia_bln'],
                $input['mens_teratur'],
                $input['mens_lama'],
                $input['mens_jarak_siklus']
            );
        }

        $validated = validateStagedRiskFactorInput($input);
        if (!$validated['valid']) {
            throw new InvalidArgumentException(implode(' ', $validated['errors']));
        }
        $scoreInput = [
            'mens_sudah' => $validated['values']['mens_sudah'],
            'mens_teratur' => $validated['values']['mens_teratur'],
            ...$validated['values']['diet_habits'],
        ];
        $score = $this->score->riskFactors($scoreInput);
        $snapshot = $this->snapshot->withRiskFactors(
            (string) $screening['answers_snapshot'],
            $validated['values'],
            $score
        );
        $this->store->completeRiskFactorScreening(
            $userId,
            $questionnaireId,
            $validated['values'],
            $score,
            $snapshot
        );

        return [
            'questionnaire_id' => $questionnaireId,
            'risk_factor_percentage' => $score['percentage'],
            'anemia_indicated' => $score['anemia_indicated'],
        ];
    }

    /** @return array<string, mixed>|null */
    public function resultForStudent(int $userId, int $questionnaireId): ?array
    {
        if ($questionnaireId < 1) {
            return null;
        }
        return $this->store->findForStudent($userId, $questionnaireId);
    }

    /** @return array<string, mixed>|null */
    public function pendingRiskFactors(int $userId): ?array
    {
        return $this->store->findLatestRiskEligible($userId);
    }

    /** @return array<string, mixed>|null */
    public function latestResultForStudent(int $userId): ?array
    {
        return $this->store->findLatestForStudent($userId);
    }
}
