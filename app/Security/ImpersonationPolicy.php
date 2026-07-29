<?php

declare(strict_types=1);

final class ImpersonationPolicy
{
    private const ALLOWED_ACTIONS = [
        'consultation.reply',
        'consultation.create',
        'questionnaire.submit',
        'ttd.record',
        'menstruation.record',
        'article.create',
        'article.update_own',
        'parent_link.request',
        'impersonation.end',
    ];

    private const BLOCKED_ACTIONS = [
        'credential.change',
        'account.role_change',
        'account.status_change',
        'superadmin.provision',
        'export.bulk',
        'record.archive',
        'record.delete',
        'configuration.change',
        'clinical.gate_change',
        'impersonation.start',
    ];

    public static function allows(string $action): bool
    {
        if (in_array($action, self::BLOCKED_ACTIONS, true)) {
            return false;
        }

        return in_array($action, self::ALLOWED_ACTIONS, true);
    }

    public static function assertAllowed(string $action): void
    {
        if (!self::allows($action)) {
            throw new DomainException(
                'Action is not permitted during impersonation.'
            );
        }
    }
}
