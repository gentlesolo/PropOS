<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$models = [
    \App\Infrastructure\Persistence\Models\Agency::class,
    \App\Infrastructure\Persistence\Models\User::class,
    \App\Infrastructure\Persistence\Models\Contact::class,
    \App\Infrastructure\Persistence\Models\Property::class,
    \App\Infrastructure\Persistence\Models\Listing::class,
    \App\Infrastructure\Persistence\Models\Deal::class,
    \App\Infrastructure\Persistence\Models\Contract::class,
    \App\Infrastructure\Persistence\Models\Viewing::class,
    \App\Infrastructure\Persistence\Models\Commission::class,
    \App\Infrastructure\Persistence\Models\Transaction::class,
    \App\Infrastructure\Persistence\Models\Tenant::class,
    \App\Infrastructure\Persistence\Models\Lease::class,
    \App\Infrastructure\Persistence\Models\Invoice::class,
];

foreach ($models as $modelClass) {
    if (!class_exists($modelClass)) continue;
    $records = $modelClass::all();
    foreach ($records as $record) {
        try {
            json_encode($record->toArray(), JSON_THROW_ON_ERROR);
        } catch (\Exception $e) {
            echo "Malformed UTF-8 in $modelClass ID: {$record->id}\n";
            // let's identify the exact attribute
            foreach ($record->getAttributes() as $key => $value) {
                if (is_string($value)) {
                    if (!mb_check_encoding($value, 'UTF-8')) {
                        echo "  -> Attribute '$key' is not valid UTF-8.\n";
                    }
                }
            }
        }
    }
}
echo "Done.\n";
