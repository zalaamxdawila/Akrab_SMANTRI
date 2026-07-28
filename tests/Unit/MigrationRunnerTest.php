<?php

use PHPUnit\Framework\TestCase;

final class MigrationRunnerTest extends TestCase
{
    public function testMigrationFilesAreDiscoveredInVersionOrder(): void
    {
        $directory = sys_get_temp_dir() . '/akrab-migrations-' . bin2hex(random_bytes(4));
        mkdir($directory);

        file_put_contents($directory . '/002_second.php', '<?php return [];');
        file_put_contents($directory . '/001_first.php', '<?php return [];');
        file_put_contents($directory . '/README.md', 'ignored');

        try {
            self::assertSame(
                ['001_first.php', '002_second.php'],
                array_map('basename', MigrationRunner::discover($directory))
            );
        } finally {
            unlink($directory . '/001_first.php');
            unlink($directory . '/002_second.php');
            unlink($directory . '/README.md');
            rmdir($directory);
        }
    }

    public function testMissingMigrationDirectoryFailsClosed(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Migration directory is not available.');

        MigrationRunner::discover(__DIR__ . '/missing-migrations');
    }
}
