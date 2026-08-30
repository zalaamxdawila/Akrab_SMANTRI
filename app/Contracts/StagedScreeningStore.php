<?php

declare(strict_types=1);

interface StagedScreeningStore
{
    /** @param array<string, mixed> $values @param array<string, mixed> $score */
    public function createSymptomScreening(
        int $userId,
        array $values,
        array $score,
        string $snapshot
    ): int;

    /** @return array<string, mixed>|null */
    public function findRiskEligible(int $userId, int $questionnaireId): ?array;

    /** @return array<string, mixed>|null */
    public function findLatestRiskEligible(int $userId): ?array;

    /** @param array<string, mixed> $values @param array<string, mixed> $score */
    public function completeRiskFactorScreening(
        int $userId,
        int $questionnaireId,
        array $values,
        array $score,
        string $snapshot
    ): void;

    /** @return array<string, mixed>|null */
    public function findForStudent(int $userId, int $questionnaireId): ?array;

    /** @return array<string, mixed>|null */
    public function findLatestForStudent(int $userId): ?array;
}
