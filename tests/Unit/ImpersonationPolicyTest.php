<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/app/Security/ImpersonationPolicy.php';

final class ImpersonationPolicyTest extends TestCase
{
    public function testOperationalActionsRemainAllowed(): void
    {
        self::assertTrue(ImpersonationPolicy::allows('consultation.reply'));
        self::assertTrue(ImpersonationPolicy::allows('questionnaire.submit'));
    }

    public function testCriticalActionsAreAlwaysBlocked(): void
    {
        foreach ([
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
        ] as $action) {
            self::assertFalse(ImpersonationPolicy::allows($action), $action);
        }
    }

    public function testUnknownActionFailsClosed(): void
    {
        self::assertFalse(ImpersonationPolicy::allows('unknown.action'));
    }

    public function testCentralAuthorizationUsesImpersonationDenyPolicy(): void
    {
        $context = new ActorContext(1, 2, 'superadmin', 'siswa', 10);

        self::assertTrue(
            actionAllowedForActor($context, 'questionnaire.submit')
        );
        self::assertFalse(
            actionAllowedForActor($context, 'credential.change')
        );
    }
}
