<?php
$files = [
    'resources/views/livewire/compliance/inspections-page.blade.php',
    'resources/views/livewire/compliance/transaction-center-page.blade.php',
    'resources/views/livewire/compliance/transaction-detail-page.blade.php',
    'resources/views/livewire/crm/deal-detail-page.blade.php',
    'resources/views/livewire/finance/budgeting-page.blade.php',
    'resources/views/livewire/finance/expenses-page.blade.php',
    'resources/views/livewire/finance/invoices-page.blade.php',
    'resources/views/livewire/intelligence/cma-report-page.blade.php',
    'resources/views/livewire/intelligence/listing-health-dashboard.blade.php',
    'resources/views/livewire/intelligence/prediction-dashboard-page.blade.php',
    'resources/views/livewire/intelligence/revenue-forecast-page.blade.php',
    'resources/views/livewire/listing/public-pocket-listing-page.blade.php',
    'resources/views/livewire/marketing/meta-ads-page.blade.php',
    'resources/views/livewire/property-management/tenant-management-page.blade.php'
];

mb_substitute_character(0xFFFD);

foreach ($files as $file) {
    if (file_exists($file)) {
        $c = file_get_contents($file);
        if (!mb_check_encoding($c, 'UTF-8')) {
            $clean = mb_convert_encoding($c, 'UTF-8', 'UTF-8');
            // We know it's a malformed Naira symbol in most cases, 
            // so we replace the replacement character with the HTML entity for Naira.
            // However, a 3-byte invalid UTF-8 sequence might become three '?' characters.
            // Let's replace 1 or more consecutive replacement chars with a single &#8358;
            $clean = preg_replace('/(\xEF\xBF\xBD)+/', '&#8358;', $clean);
            
            file_put_contents($file, $clean);
            echo "Fixed $file\n";
        }
    } else {
        echo "Missing $file\n";
    }
}
echo "Done.\n";
