<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$projectRoot = dirname(__DIR__);
$target = $argv[1] ?? '';

if ($target === '') {
    fwrite(STDERR, "Usage: php tools/build_release.php <new-empty-target-directory>\n");
    exit(1);
}

$target = rtrim(str_replace('\\', '/', $target), '/');
if (file_exists($target)) {
    fwrite(STDERR, "Target already exists; refusing to overwrite it.\n");
    exit(1);
}

if (!mkdir($target, 0750, true)) {
    fwrite(STDERR, "Unable to create target directory.\n");
    exit(1);
}

$lines = file($projectRoot . '/deployment/include.txt', FILE_IGNORE_NEW_LINES);
if ($lines === false) {
    fwrite(STDERR, "Unable to read deployment/include.txt.\n");
    exit(1);
}

$included = [];
foreach ($lines as $line) {
    $relative = trim($line);
    if ($relative === '' || str_starts_with($relative, '#')) {
        continue;
    }

    $source = $projectRoot . '/' . rtrim($relative, '/');
    if (!file_exists($source)) {
        fwrite(STDERR, "Allowlisted path does not exist: {$relative}\n");
        exit(1);
    }

    if (is_dir($source)) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = str_replace('\\', '/', $file->getPathname());
            copyReleaseFile($projectRoot, $target, $path, $included);
        }
        continue;
    }

    copyReleaseFile($projectRoot, $target, str_replace('\\', '/', $source), $included);
}

sort($included);
$files = [];
foreach ($included as $relative) {
    $files[] = [
        'path' => $relative,
        'sha256' => hash_file('sha256', $target . '/' . $relative),
        'bytes' => filesize($target . '/' . $relative),
    ];
}

$manifest = [
    'schema' => 1,
    'release_id' => gmdate('Ymd\THis\Z'),
    'source_commit' => sourceCommit($projectRoot),
    'generated_at' => gmdate(DATE_ATOM),
    'files' => $files,
];
$manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($manifestJson === false) {
    fwrite(STDERR, "Unable to encode release manifest.\n");
    exit(1);
}
file_put_contents($target . '/release-manifest.json', $manifestJson . PHP_EOL);
file_put_contents(
    $target . '/release-manifest.sha256',
    hash_file('sha256', $target . '/release-manifest.json') . "  release-manifest.json\n"
);

echo "Release candidate built at {$target}\n";
echo 'Files: ' . count($files) . PHP_EOL;

function copyReleaseFile(string $root, string $target, string $source, array &$included): void
{
    $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');
    if (!str_starts_with($source, $normalizedRoot . '/')) {
        throw new RuntimeException('Source escaped project root.');
    }

    $relative = substr($source, strlen($normalizedRoot) + 1);
    $destination = $target . '/' . $relative;
    $directory = dirname($destination);
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException("Unable to create directory for {$relative}");
    }
    if (!copy($source, $destination)) {
        throw new RuntimeException("Unable to copy {$relative}");
    }
    $included[] = $relative;
}

function sourceCommit(string $root): string
{
    $output = [];
    $exitCode = 0;
    exec('git -C ' . escapeshellarg($root) . ' rev-parse HEAD 2>NUL', $output, $exitCode);
    return $exitCode === 0 && isset($output[0]) ? trim($output[0]) : 'unknown';
}
