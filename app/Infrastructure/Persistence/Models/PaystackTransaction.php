<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaystackTransaction extends Model
{
    protected $fillable = [
        'agency_id',
        'reference',
        'type',
        'event',
        'status',
        'amount',
        'currency',
        'plan',
        'billing_cycle',
        'credits_added',
        'paystack_transaction_id',
        'paystack_customer_code',
        'paystack_subscription_code',
        'authorization_code',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'amount'   => 'integer',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function getAmountInNairaAttribute(): float
    {
        return $this->amount / 100;
    }
}
