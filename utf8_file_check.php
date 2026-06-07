<?php
$dirs = ['app', 'resources', 'config'];
foreach($dirs as $d) {
    if (!is_dir($d)) continue;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d));
    foreach($files as $f) {
        if($f->isFile() && in_array($f->getExtension(), ['php','html','css','js'])) {
            $c = file_get_contents($f->getPathname());
            if(!mb_check_encoding($c, 'UTF-8')) {
                echo "Invalid UTF-8 in: " . $f->getPathname() . "\n";
            }
        }
    }
}
echo "Done.\n";
