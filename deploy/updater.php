<?php

/**
 * VillaCRM Updater Script for Self-Hosted Deployments.
 */

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "========================================\n";
echo "       VillaCRM Updater Starting           \n";
echo "========================================\n\n";

try {
    // 1. Maintenance Mode On
    echo "Putting application in maintenance mode...\n";
    \Illuminate\Support\Facades\Artisan::call('down', ['--refresh' => 15]);
    echo \Illuminate\Support\Facades\Artisan::output();

    // 2. Run migrations
    echo "Running database migrations...\n";
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    echo \Illuminate\Support\Facades\Artisan::output();

    // 3. Clear Caches
    echo "Clearing application caches...\n";
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    echo "Caches cleared successfully.\n";

    // 4. Maintenance Mode Off
    echo "Bringing application online...\n";
    \Illuminate\Support\Facades\Artisan::call('up');
    echo \Illuminate\Support\Facades\Artisan::output();

    echo "========================================\n";
    echo "          Update Completed!             \n";
    echo "========================================\n";
} catch (\Exception $e) {
    echo "Update failed with error: " . $e->getMessage() . "\n";
    // Ensure we bring the app back up
    try {
        \Illuminate\Support\Facades\Artisan::call('up');
    } catch (\Exception $ex) {}
    exit(1);
}
