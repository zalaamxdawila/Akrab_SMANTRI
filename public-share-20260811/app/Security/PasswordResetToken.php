<?php

declare(strict_types=1);

final class PasswordResetToken
{
    /**
     * @return array{token:string,digest:string}
     */
    public static function issue(): array
    {
        $token = bin2hex(random_bytes(32));

        return [
            'token' => $token,
            'digest' => self::digest($token),
        ];
    }

    public static function digest(string $token): string
    {
        if (preg_match('/^[a-f0-9]{64}$/D', $token) !== 1) {
            throw new InvalidArgumentException('Invalid password reset token.');
        }

        return hash('sha256', $token);
    }
}
