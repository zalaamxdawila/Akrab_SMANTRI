<?php

declare(strict_types=1);

const AKRAB_LOG_LEVELS = ['debug', 'info', 'warn', 'error'];
const AKRAB_LOG_CONTEXT_KEYS = [
    'action', 'actor_role', 'duration_ms', 'exception_class', 'method',
    'outcome', 'route', 'status_code', 'target_type',
];

function safeLogContext(array $context): array
{
    $safe = [];
    foreach (AKRAB_LOG_CONTEXT_KEYS as $key) {
        if (!array_key_exists($key, $context) || (!is_scalar($context[$key]) && $context[$key] !== null)) {
            continue;
        }
        $value = $context[$key];
        $safe[$key] = is_string($value) ? substr($value, 0, 120) : $value;
    }
    return $safe;
}

function akrabLog(string $level, string $event, array $context = []): void
{
    if (!in_array($level, AKRAB_LOG_LEVELS, true)) {
        $level = 'error';
    }
    $record = array_merge([
        'timestamp' => gmdate('c'),
        'level' => $level,
        'event' => preg_replace('/[^a-z0-9_.-]/', '_', strtolower($event)) ?: 'invalid_event',
        'correlation_id' => requestCorrelationId(),
    ], safeLogContext($context));
    error_log(json_encode($record, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
}

function recordAuditEvent(PDO $pdo, ?int $actorId, string $action, string $targetType, ?int $targetId, array $metadata = []): void
{
    $safeMetadata = safeLogContext($metadata);
    $statement = $pdo->prepare(
        'INSERT INTO audit_log (actor_id, action, target_type, target_id, metadata_json)
         VALUES (?, ?, ?, ?, ?)'
    );
    $statement->execute([
        $actorId,
        substr($action, 0, 80),
        substr($targetType, 0, 50),
        $targetId,
        $safeMetadata ? json_encode($safeMetadata, JSON_THROW_ON_ERROR) : null,
    ]);
}
