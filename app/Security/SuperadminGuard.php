<?php

declare(strict_types=1);

require_once __DIR__ . '/ActorContext.php';
require_once __DIR__ . '/ActorContextResolver.php';

final class SuperadminGuard
{
    public static function authorize(PDO $pdo, array $session): ActorContext
    {
        $context = (new ActorContextResolver($pdo))->resolve($session);
        if (!self::contextIsAuthorized($context)) {
            throw new DomainException('Superadmin access is denied.');
        }

        return $context;
    }

    public static function contextIsAuthorized(ActorContext $context): bool
    {
        return !$context->isImpersonating()
            && $context->authenticatedActorId === $context->effectiveActorId
            && $context->authenticatedRole === 'superadmin'
            && $context->effectiveRole === 'superadmin'
            && roleCan('superadmin', 'view_master_dashboard');
    }
}
