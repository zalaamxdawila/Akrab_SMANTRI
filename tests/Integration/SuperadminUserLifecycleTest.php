<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Services/SuperadminUserService.php';

final class SuperadminUserLifecycleTest extends TestCase
{
    public function testArchiveIsReversibleAndSuperadminIsImmutable(): void
    {
        $pdo = Sprint28Fixture::database();
        $service = new SuperadminUserService($pdo);
        $service->changeStatus(Sprint28Fixture::actor(), 2, 'archived', 'data_governance', 'one');
        self::assertSame('archived', $pdo->query('SELECT status FROM users WHERE id = 2')->fetchColumn());
        $service->changeStatus(Sprint28Fixture::actor(), 2, 'active', 'verification', 'two');
        self::assertSame('active', $pdo->query('SELECT status FROM users WHERE id = 2')->fetchColumn());

        $this->expectException(DomainException::class);
        $service->changeStatus(Sprint28Fixture::actor(), 1, 'inactive', 'support', 'three');
    }

    public function testImpersonatedActorCannotMutate(): void
    {
        $this->expectException(DomainException::class);
        (new SuperadminUserService(Sprint28Fixture::database()))->changeStatus(
            new ActorContext(1, 2, 'superadmin', 'siswa', 99, 'support'),
            2,
            'archived',
            'support',
            'req'
        );
    }
}
