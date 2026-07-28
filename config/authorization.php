<?php

declare(strict_types=1);

function applicationRoles(): array
{
    return ['siswa', 'orangtua', 'uks'];
}

function isApplicationRole(string $role): bool
{
    return in_array($role, applicationRoles(), true);
}

function dashboardForRole(string $role): string
{
    $dashboards = [
        'siswa' => 'siswa/dashboard.php',
        'orangtua' => 'orangtua/dashboard.php',
        'uks' => 'uks/dashboard.php',
    ];

    if (!isset($dashboards[$role])) {
        throw new InvalidArgumentException('Unknown application role.');
    }

    return $dashboards[$role];
}

function roleCan(string $role, string $action): bool
{
    $permissions = [
        'siswa' => ['manage_own_health', 'read_education', 'ask_consultation'],
        'orangtua' => ['view_linked_child'],
        'uks' => ['manage_school_health', 'reply_consultation', 'manage_own_articles'],
    ];

    return isset($permissions[$role])
        && in_array($action, $permissions[$role], true);
}
