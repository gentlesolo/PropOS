<?php

$dir = new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS);
$iterator = new RecursiveIteratorIterator($dir);

$excludeDirs = ['.git', 'vendor', 'node_modules', 'storage', 'bootstrap'];
$extensions = ['php', 'tsx', 'ts', 'js', 'json', 'md', 'cjs', 'sh', 'blade.php', ''];

$replacements = [
    'PropOS' => 'VillaCRM',
    'propos.com' => 'villacrm.com',
    'propos-' => 'villacrm-',
    'propos_' => 'villacrm_',
    "'propos'" => "'villacrm'",
    '"propos"' => '"villacrm"',
    'ProposMobile' => 'VillaCRMMobile',
    'propos.mobile' => 'villacrm.mobile',
    'Propos' => 'VillaCRM'
];

$count = 0;

foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    
    $path = $file->getPathname();
    
    // Skip excluded dirs
    $skip = false;
    foreach ($excludeDirs as $exc) {
        if (strpos($path, DIRECTORY_SEPARATOR . $exc . DIRECTORY_SEPARATOR) !== false) {
            $skip = true;
            break;
        }
    }
    if ($skip) continue;

    $ext = pathinfo($path, PATHINFO_EXTENSION);
    $filename = basename($path);
    
    if (in_array($filename, ['replace_brand.php', 'replace.php', 'replace-tokens.cjs', 'replace-tailwind-colors.cjs'])) {
        continue;
    }

    $content = file_get_contents($path);
    if ($content === false) continue;
    
    $original = $content;
    
    foreach ($replacements as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }

    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "Updated: " . str_replace(__DIR__, '', $path) . "\n";
        $count++;
    }
}

echo "\nTotal files updated: $count\n";
