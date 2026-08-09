<?php

declare(strict_types=1);

final class QuestionnaireEligibility
{
    private const REOPENING_AT = '2026-08-17 00:00:00';

    /**
     * @return array{
     *   allowed:bool,
     *   reason:string,
     *   next_eligible_at:?DateTimeImmutable
     * }
     */
    public function forLatestSubmission(
        ?string $latestCreatedAt,
        ?DateTimeImmutable $now = null
    ): array {
        $now ??= new DateTimeImmutable('now');

        if ($latestCreatedAt === null || trim($latestCreatedAt) === '') {
            return [
                'allowed' => true,
                'reason' => 'first_submission',
                'next_eligible_at' => null,
            ];
        }

        $latest = new DateTimeImmutable($latestCreatedAt, $now->getTimezone());
        $cooldownEnds = $latest->modify('+6 months');

        if ($now >= $cooldownEnds) {
            return [
                'allowed' => true,
                'reason' => 'cooldown_elapsed',
                'next_eligible_at' => null,
            ];
        }

        $reopening = new DateTimeImmutable(
            self::REOPENING_AT,
            $now->getTimezone()
        );
        if ($latest < $reopening && $now >= $reopening) {
            return [
                'allowed' => true,
                'reason' => 'reopened',
                'next_eligible_at' => null,
            ];
        }

        $nextEligibleAt = $latest < $reopening && $reopening < $cooldownEnds
            ? $reopening
            : $cooldownEnds;

        return [
            'allowed' => false,
            'reason' => 'cooldown',
            'next_eligible_at' => $nextEligibleAt,
        ];
    }
}
