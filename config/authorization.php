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
        'superadmin' => [
            'view_master_dashboard',
            'manage_users',
            'manage_parent_links',
            'manage_health_records',
            'manage_operations',
        ],
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

function impersonationActionForRequest(string $scriptName, array $input): string
{
    $path = str_replace('\\', '/', $scriptName);
    if (str_ends_with($path, '/siswa/kuesioner.php')) {
        return 'questionnaire.submit';
    }
    if (str_ends_with($path, '/siswa/konsultasi.php')) {
        return 'consultation.create';
    }
    if (str_ends_with($path, '/uks/jawab_konsultasi.php')) {
        return 'consultation.reply';
    }
    if (str_ends_with($path, '/uks/kelola_artikel.php')) {
        return isset($input['id']) ? 'article.update_own' : 'article.create';
    }
    if (str_ends_with($path, '/orangtua/dashboard.php')) {
        return 'parent_link.request';
    }
    if (str_ends_with($path, '/siswa/dashboard.php')) {
        return isset($input['toggle_haid'])
            ? 'menstruation.record'
            : 'ttd.record';
    }
    if (str_ends_with($path, '/end_impersonation.php')) {
        return 'impersonation.end';
    }
    return 'impersonation.route_unapproved';
}
