<?php
$dir = new RecursiveDirectoryIterator(__DIR__);
$iterator = new RecursiveIteratorIterator($dir);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && $file->getFilename() !== 'inject.php') {
        $content = file_get_contents($file->getPathname());
        
        // Skip if already injected
        if (strpos($content, 'app-init.js') !== false) {
            continue;
        }
        
        // Inject script before </body>
        if (strpos($content, '</body>') !== false) {
            $script = '<script src="/assets/js/app-init.js"></script>' . "\n" . '</body>';
            $content = str_replace('</body>', $script, $content);
            file_put_contents($file->getPathname(), $content);
            echo "Injected into: " . $file->getPathname() . "\n";
        }
    }
}
echo "Injection complete!\n";
