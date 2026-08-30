<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Contracts/QuestionnaireRetakeStore.php';
require_once dirname(__DIR__, 2) . '/config/authorization.php';

final class QuestionnaireRetakeService
{
    public function __construct(private QuestionnaireRetakeStore $store)
    {
    }

    public function enableRetake(
        ActorContext $actor,
        int $studentId,
        string $reason,
        string $requestId
    ): int {
        $allowedRoles = ['uks', 'superadmin'];
        $requiredAction = $actor->effectiveRole === 'uks'
            ? 'manage_school_health'
            : 'manage_health_records';

        if (
            $studentId < 1
            || $actor->isImpersonating()
            || $actor->authenticatedActorId !== $actor->effectiveActorId
            || !in_array($actor->effectiveRole, $allowedRoles, true)
            || !roleCan($actor->effectiveRole, $requiredAction)
        ) {
            throw new DomainException('Aksi isi ulang kuesioner ditolak.');
        }

        $reason = trim($reason);
        $reasonLength = mb_strlen($reason);
        if ($reasonLength < 5 || $reasonLength > 500) {
            throw new InvalidArgumentException(
                'Alasan reset wajib diisi 5–500 karakter.'
            );
        }

        return $this->store->enableRetake(
            $actor,
            $studentId,
            $reason,
            $requestId
        );
    }
}
