<?php

return [
    'late_fee_grace_days'    => 3,
    'late_fee_min_fixed'     => 250.00,
    'late_fee_rate'          => 0.10,
    'default_payment_method' => 'payfast',
    'invoice_due_day_offset' => 0,
    'vat_number'             => env('AGENCY_VAT_NUMBER'),
];
