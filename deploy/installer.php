<?php

/**
 * PropOS Installer Script for Self-Hosted Deployments.
 */

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "========================================\n";
echo "       PropOS Installer Starting        \n";
echo "========================================\n\n";

try {
    // 1. Check requirements
    echo "Checking requirements...\n";
    if (version_compare(PHP_VERSION, '8.3.0', '<')) {
        die("Error: PHP 8.3.0 or higher is required. Current version: " . PHP_VERSION . "\n");
    }
    echo "PHP version check passed: " . PHP_VERSION . "\n";

    // 2. Run migrations
    echo "Running database migrations...\n";
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo \Illuminate\Support\Facades\Artisan::output();

    // 3. Seed roles and default agency
    echo "Seeding default roles and configurations...\n";
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
    echo \Illuminate\Support\Facades\Artisan::output();

    echo "========================================\n";
    echo "       Installation Completed!          \n";
    echo "========================================\n";
} catch (\Exception $e) {
    echo "Installation failed with error: " . $e->getMessage() . "\n";
    exit(1);
}
