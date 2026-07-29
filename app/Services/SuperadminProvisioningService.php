<?php

declare(strict_types=1);

final class SuperadminProvisioningService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function provision(string $name, string $username, string $password): int
    {
        $name = trim($name);
        $username = trim($username);
        $this->assertValidInput($name, $username, $password);

        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) {
            $this->pdo->beginTransaction();
        }

        try {
            $query = "SELECT id, username FROM users WHERE role = 'superadmin' LIMIT 1";
            if ($this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
                $query .= ' FOR UPDATE';
            }
            $existing = $this->pdo->query($query)->fetch();

            if ($existing && !hash_equals((string) $existing['username'], $username)) {
                throw new DomainException('A superadmin account already exists.');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            if ($passwordHash === false) {
                throw new RuntimeException('Password hashing failed.');
            }

            if ($existing) {
                $userId = (int) $existing['id'];
                $statement = $this->pdo->prepare(
                    "UPDATE users
                     SET nama = ?, password_hash = ?, status = 'active'
                     WHERE id = ? AND role = 'superadmin'"
                );
                $statement->execute([$name, $passwordHash, $userId]);
                $action = 'superadmin.recovered';
                $mode = 'recovery';
            } else {
                $statement = $this->pdo->prepare(
                    "INSERT INTO users (nama, role, status, username, password_hash)
                     VALUES (?, 'superadmin', 'active', ?, ?)"
                );
                $statement->execute([$name, $username, $passwordHash]);
                $userId = (int) $this->pdo->lastInsertId();
                $action = 'superadmin.provisioned';
                $mode = 'create';
            }

            $audit = $this->pdo->prepare(
                'INSERT INTO audit_log
                    (actor_id, action, target_type, target_id, metadata_json)
                 VALUES (NULL, ?, ?, ?, ?)'
            );
            $audit->execute([
                $action,
                'user',
                $userId,
                json_encode(
                    ['operator' => 'cli', 'mode' => $mode],
                    JSON_THROW_ON_ERROR
                ),
            ]);

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return $userId;
        } catch (Throwable $exception) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function assertValidInput(
        string $name,
        string $username,
        string $password
    ): void {
        if (
            strlen($name) < 2
            || strlen($name) > 100
            || !preg_match('/\A[A-Za-z0-9._-]{3,50}\z/D', $username)
            || strlen($password) < 12
            || strlen($password) > 1024
        ) {
            throw new InvalidArgumentException('Invalid superadmin provisioning input.');
        }
    }
}
