<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Services/SuperadminUserService.php';

final class SuperadminUserCreateTest extends TestCase
{
    public function testCreatesOnlyAllowedRoleWithHashedPasswordAndAudit(): void
    {
        $pdo = Sprint28Fixture::database();
        $actor = Sprint28Fixture::actor();
        $id = (new SuperadminUserService($pdo))->create($actor, [
            'nama' => 'Siswa Baru',
            'username' => 'siswa.baru',
            'role' => 'siswa',
            'password' => 'rahasia-awal-yang-kuat',
            'kelas' => 'VIII A',
        ], 'req-create');

        $row = $pdo->query("SELECT * FROM users WHERE id = {$id}")->fetch();
        self::assertTrue(password_verify('rahasia-awal-yang-kuat', $row['password_hash']));
        self::assertNotSame('rahasia-awal-yang-kuat', $row['password_hash']);
        self::assertSame('user.created', $pdo->query(
            'SELECT action FROM audit_log ORDER BY id DESC LIMIT 1'
        )->fetchColumn());
    }

    public function testRejectsSuperadminCreation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SuperadminUserService(Sprint28Fixture::database()))->create(
            Sprint28Fixture::actor(),
            ['nama' => 'Master Dua', 'username' => 'master2', 'role' => 'superadmin',
                'password' => 'rahasia-awal-yang-kuat'],
            'req'
        );
    }
}
