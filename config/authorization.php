<?php

declare(strict_types=1);

function applicationRoles(): array
{
    return ['siswa', 'orangtua', 'uks', 'superadmin'];
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
        'superadmin' => 'superadmin/dashboard.php',
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
        'superadmin' => ['view_master_dashboard'],
    ];

    return isset($permissions[$role])
        && in_array($action, $permissions[$role], true);
}

function superadminFeatureEnabled(): bool
{
    return filter_var(
        getenv('AKRAB_SUPERADMIN_ENABLED') ?: 'false',
        FILTER_VALIDATE_BOOLEAN,
        FILTER_NULL_ON_FAILURE
    ) === true;
}

function roleIsEnabled(string $role): bool
{
    return isApplicationRole($role)
        && ($role !== 'superadmin' || superadminFeatureEnabled());
}

function userCanAuthenticate(array $user): bool
{
    $role = (string) ($user['role'] ?? '');
    $status = (string) ($user['status'] ?? '');

    return $status === 'active' && roleIsEnabled($role);
}

function actionAllowedForActor(ActorContext $context, string $action): bool
{
    if ($context->isImpersonating()) {
        return ImpersonationPolicy::allows($action);
    }

    return roleCan($context->effectiveRole, $action);
}
