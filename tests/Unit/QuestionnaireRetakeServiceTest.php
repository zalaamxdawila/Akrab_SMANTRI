<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class QuestionnaireRetakeServiceTest extends TestCase
{
    public function testUksCanEnableRetakeForAStudent(): void
    {
        $store = new InMemoryQuestionnaireRetakeStore();
        $service = new QuestionnaireRetakeService($store);
        $actor = new ActorContext(7, 7, 'uks', 'uks');

        $updated = $service->enableRetake(
            $actor,
            21,
            'Siswa perlu mengulang setelah evaluasi UKS.',
            'request-1'
        );

        self::assertSame(2, $updated);
        self::assertSame(21, $store->studentId);
        self::assertSame('request-1', $store->requestId);
        self::assertSame(
            'Siswa perlu mengulang setelah evaluasi UKS.',
            $store->reason
        );
        self::assertSame(7, $store->actor?->authenticatedActorId);
    }

    public function testSuperadminCanEnableRetakeForAStudent(): void
    {
        $store = new InMemoryQuestionnaireRetakeStore();
        $service = new QuestionnaireRetakeService($store);
        $actor = new ActorContext(1, 1, 'superadmin', 'superadmin');

        self::assertSame(2, $service->enableRetake(
            $actor,
            21,
            'Koreksi periode pengisian.',
            'request-2'
        ));
    }

    public function testStudentCannotEnableOwnRetake(): void
    {
        $service = new QuestionnaireRetakeService(
            new InMemoryQuestionnaireRetakeStore()
        );

        $this->expectException(DomainException::class);
        $service->enableRetake(
            new ActorContext(21, 21, 'siswa', 'siswa'),
            21,
            'Mencoba reset mandiri.',
            'request-3'
        );
    }

    public function testImpersonatingSuperadminCannotEnableRetake(): void
    {
        $service = new QuestionnaireRetakeService(
            new InMemoryQuestionnaireRetakeStore()
        );

        $this->expectException(DomainException::class);
        $service->enableRetake(
            new ActorContext(1, 21, 'superadmin', 'siswa', 33, 'support'),
            21,
            'Mencoba reset saat login as.',
            'request-4'
        );
    }

    public function testResetReasonIsRequiredAndBounded(): void
    {
        $service = new QuestionnaireRetakeService(
            new InMemoryQuestionnaireRetakeStore()
        );

        $this->expectException(InvalidArgumentException::class);
        $service->enableRetake(
            new ActorContext(7, 7, 'uks', 'uks'),
            21,
            '   ',
            'request-5'
        );
    }
}

final class InMemoryQuestionnaireRetakeStore implements QuestionnaireRetakeStore
{
    public ?ActorContext $actor = null;
    public ?int $studentId = null;
    public ?string $reason = null;
    public ?string $requestId = null;

    public function enableRetake(
        ActorContext $actor,
        int $studentId,
        string $reason,
        string $requestId
    ): int {
        $this->actor = $actor;
        $this->studentId = $studentId;
        $this->reason = $reason;
        $this->requestId = $requestId;
        return 2;
    }
}
