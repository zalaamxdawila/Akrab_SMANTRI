<?php
// zip_project.php
// Script to zip the project directory for deployment

$dir = __DIR__;
$zipFile = dirname(__DIR__) . '/akrab_deploy.zip';

$zip = new ZipArchive();
if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    // Create recursive directory iterator
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        // Skip directories (they would be added automatically)
        if (!$file->isDir()) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($dir) + 1);

            // Skip zip_project.php itself and .git or .agents directories
            if (strpos($relativePath, 'zip_project.php') !== false || strpos($relativePath, '.git') === 0 || strpos($relativePath, '.agents') === 0) {
                continue;
            }

            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }
    $zip->close();
    echo "Successfully created zip file: " . $zipFile;
} else {
    echo "Failed to create zip file.";
}
?>
