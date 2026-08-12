<?php

final class MigrationRunner
{
    private PDO $pdo;
    private string $directory;

    public function __construct(PDO $pdo, string $directory)
    {
        $this->pdo = $pdo;
        $this->directory = $directory;
    }

    public static function discover(string $directory): array
    {
        if (!is_dir($directory)) {
            throw new RuntimeException('Migration directory is not available.');
        }

        $files = glob(rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . '[0-9][0-9][0-9]_*.php');
        sort($files, SORT_STRING);

        return $files;
    }

    public function migrate(): array
    {
        $this->ensureTrackingTable();
        $applied = $this->appliedVersions();
        $completed = [];

        foreach (self::discover($this->directory) as $file) {
            $migration = require $file;
            $this->assertValidMigration($migration, $file);

            if (isset($applied[$migration['version']])) {
                continue;
            }

            $migration['up']($this->pdo);
            $statement = $this->pdo->prepare(
                'INSERT INTO schema_migrations (version, description) VALUES (?, ?)'
            );
            $statement->execute([$migration['version'], $migration['description']]);
            $completed[] = $migration['version'];
        }

        return $completed;
    }

    public static function executeSqlFile(PDO $pdo, string $path): void
    {
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException('Migration SQL file cannot be read.');
        }

        foreach (preg_split('/;\s*(?:\r?\n|$)/', $sql) as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }
    }

    private function ensureTrackingTable(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(100) PRIMARY KEY,
                description VARCHAR(255) NOT NULL,
                applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function appliedVersions(): array
    {
        $versions = [];
        $statement = $this->pdo->query('SELECT version FROM schema_migrations');

        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $version) {
            $versions[$version] = true;
        }

        return $versions;
    }

    private function assertValidMigration($migration, string $file): void
    {
        if (
            !is_array($migration)
            || !isset($migration['version'], $migration['description'], $migration['up'])
            || !is_string($migration['version'])
            || !is_string($migration['description'])
            || !is_callable($migration['up'])
        ) {
            throw new RuntimeException('Invalid migration definition: ' . basename($file));
        }
    }
}
