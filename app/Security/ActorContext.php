<?php

declare(strict_types=1);

final readonly class ActorContext
{
    public function __construct(
        public int $authenticatedActorId,
        public int $effectiveActorId,
        public string $authenticatedRole,
        public string $effectiveRole,
        public ?int $impersonationSessionId = null,
        public ?string $impersonationReasonCategory = null
    ) {
    }

    public function isImpersonating(): bool
    {
        return $this->impersonationSessionId !== null;
    }
}
