<?php

declare(strict_types=1);

interface QuestionnaireRetakeStore
{
    public function enableRetake(
        ActorContext $actor,
        int $studentId,
        string $reason,
        string $requestId
    ): int;
}
