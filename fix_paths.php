<?php
$dir = new RecursiveDirectoryIterator(__DIR__);
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && $file->getFilename() !== 'fix_paths.php') {
        $content = file_get_contents($file->getPathname());
        
        // Determine relative depth
        $path = $file->getPathname();
        $isSubdir = false;
        
        // Check if it's in a subdirectory like 'siswa', 'uks', 'orangtua'
        if (strpos($path, DIRECTORY_SEPARATOR . 'siswa' . DIRECTORY_SEPARATOR) !== false ||
            strpos($path, DIRECTORY_SEPARATOR . 'uks' . DIRECTORY_SEPARATOR) !== false ||
            strpos($path, DIRECTORY_SEPARATOR . 'orangtua' . DIRECTORY_SEPARATOR) !== false) {
            $isSubdir = true;
        }
        
        $correctPath = $isSubdir ? '../assets/js/app-init.js' : 'assets/js/app-init.js';
        
        if (strpos($content, '/assets/js/app-init.js') !== false) {
            $content = str_replace('/assets/js/app-init.js', $correctPath, $content);
            file_put_contents($file->getPathname(), $content);
            echo "Fixed path in: " . $file->getPathname() . "\n";
        }
    }
}
echo "Path fixing complete!\n";
