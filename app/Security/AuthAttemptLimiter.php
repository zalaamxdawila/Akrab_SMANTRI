<?php

declare(strict_types=1);

final class AuthAttemptLimiter
{
    public static function allows(
        array &$session,
        string $bucket,
        ?int $now = null,
        int $maximumAttempts = 5,
        int $windowSeconds = 900
    ): bool {
        self::prune($session, $bucket, $now ?? time(), $windowSeconds);

        return count($session[self::key($bucket)] ?? []) < $maximumAttempts;
    }

    public static function record(
        array &$session,
        string $bucket,
        ?int $now = null,
        int $windowSeconds = 900
    ): void {
        $timestamp = $now ?? time();
        self::prune($session, $bucket, $timestamp, $windowSeconds);
        $key = self::key($bucket);
        $attempts = $session[$key] ?? [];
        $attempts[] = $timestamp;
        $session[$key] = array_slice($attempts, -100);
    }

    public static function clear(array &$session, string $bucket): void
    {
        unset($session[self::key($bucket)]);
    }

    private static function prune(
        array &$session,
        string $bucket,
        int $now,
        int $windowSeconds
    ): void {
        if ($windowSeconds < 1) {
            throw new InvalidArgumentException('Rate-limit window must be positive.');
        }

        $key = self::key($bucket);
        $cutoff = $now - $windowSeconds;
        $session[$key] = array_values(array_filter(
            (array) ($session[$key] ?? []),
            static fn (mixed $timestamp): bool => is_int($timestamp)
                && $timestamp > $cutoff
                && $timestamp <= $now
        ));
    }

    private static function key(string $bucket): string
    {
        if (preg_match('/^[a-z0-9-]{1,40}$/D', $bucket) !== 1) {
            throw new InvalidArgumentException('Invalid rate-limit bucket.');
        }

        return '_auth_attempts_' . $bucket;
    }
}
