<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireEligibilityTest extends TestCase
{
    public function testStudentWithoutPreviousSubmissionCanFillImmediately(): void
    {
        $status = (new QuestionnaireEligibility())->forLatestSubmission(
            null,
            new DateTimeImmutable('2026-08-09 10:00:00')
        );

        self::assertTrue($status['allowed']);
        self::assertNull($status['next_eligible_at']);
    }

    public function testRecentSubmissionRemainsLockedBeforeReopeningDate(): void
    {
        $status = (new QuestionnaireEligibility())->forLatestSubmission(
            '2026-07-01 08:00:00',
            new DateTimeImmutable('2026-08-16 23:59:59')
        );

        self::assertFalse($status['allowed']);
        self::assertSame(
            '2026-08-17 00:00:00',
            $status['next_eligible_at']?->format('Y-m-d H:i:s')
        );
    }

    public function testSubmissionBeforeCutoffBecomesEligibleAtReopening(): void
    {
        $status = (new QuestionnaireEligibility())->forLatestSubmission(
            '2026-08-16 23:59:59',
            new DateTimeImmutable('2026-08-17 00:00:00')
        );

        self::assertTrue($status['allowed']);
        self::assertSame('reopened', $status['reason']);
    }

    public function testSubmissionOnReopeningDateStartsANewSixMonthCooldown(): void
    {
        $status = (new QuestionnaireEligibility())->forLatestSubmission(
            '2026-08-17 08:00:00',
            new DateTimeImmutable('2026-08-17 10:00:00')
        );

        self::assertFalse($status['allowed']);
        self::assertSame(
            '2027-02-17 08:00:00',
            $status['next_eligible_at']?->format('Y-m-d H:i:s')
        );
    }

    public function testSixMonthCooldownEventuallyExpiresAfterReopening(): void
    {
        $status = (new QuestionnaireEligibility())->forLatestSubmission(
            '2026-08-17 08:00:00',
            new DateTimeImmutable('2027-02-17 08:00:00')
        );

        self::assertTrue($status['allowed']);
        self::assertSame('cooldown_elapsed', $status['reason']);
    }
}
