<?php

declare(strict_types=1);

require_once __DIR__ . '/ActorContext.php';

final class ImpersonationMutationAudit
{
    public function __construct(private PDO $pdo)
    {
    }

    public function record(
        ActorContext $context,
        string $action,
        string $targetType,
        ?int $targetId,
        string $outcome,
        string $route,
        string $requestId,
        array $metadata = []
    ): void {
        $safeMetadata = [
            'outcome' => substr($outcome, 0, 40),
            'route' => substr($route, 0, 120),
        ];
        $reasonCategory = $metadata['reason_category']
            ?? $context->impersonationReasonCategory;
        if (
            is_string($reasonCategory)
            && $reasonCategory !== ''
        ) {
            $safeMetadata['reason_category'] = substr(
                $reasonCategory,
                0,
                40
            );
        }
        if (isset($metadata['changed_fields']) && is_array($metadata['changed_fields'])) {
            $safeFields = array_values(array_filter(
                $metadata['changed_fields'],
                static fn (mixed $field): bool => is_string($field)
                    && preg_match('/^[a-z][a-z0-9_]{0,39}$/', $field) === 1
            ));
            if ($safeFields !== []) {
                $safeMetadata['changed_fields'] = array_slice($safeFields, 0, 20);
            }
        }

        $statement = $this->pdo->prepare(
            'INSERT INTO audit_log (
                actor_id, authenticated_actor_id, effective_actor_id,
                impersonation_session_id, request_id, action, target_type,
                target_id, metadata_json
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $context->authenticatedActorId,
            $context->authenticatedActorId,
            $context->effectiveActorId,
            $context->impersonationSessionId,
            substr($requestId, 0, 64),
            substr($action, 0, 80),
            substr($targetType, 0, 50),
            $targetId,
            json_encode($safeMetadata, JSON_THROW_ON_ERROR),
        ]);
    }

    public function registerCurrentMutation(
        ActorContext $context,
        string $route,
        string $requestId
    ): void {
        if (!$context->isImpersonating()) {
            return;
        }

        $this->record(
            $context,
            'http.mutation_started',
            'http_request',
            null,
            'started',
            $route,
            $requestId
        );

        register_shutdown_function(function () use (
            $context,
            $route,
            $requestId
        ): void {
            $statusCode = http_response_code();
            $statusCode = is_int($statusCode) && $statusCode > 0
                ? $statusCode
                : 200;
            $outcome = $statusCode >= 400 ? 'failed' : 'success';

            try {
                $this->record(
                    $context,
                    'http.mutation',
                    'http_request',
                    null,
                    $outcome,
                    $route,
                    $requestId
                );
            } catch (Throwable $exception) {
                if (function_exists('akrabLog')) {
                    akrabLog(
                        'error',
                        'impersonation_audit_failed',
                        ['exception_class' => get_class($exception)]
                    );
                }
            }
        });
    }
}
