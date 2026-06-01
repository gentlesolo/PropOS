<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMandate extends Model
{
    use HasFactory, BelongsToAgency;

    protected $guarded = ['id'];

    protected $casts = [
        'amount'               => 'decimal:2',
        'last_collected_at'    => 'datetime',
        'next_collection_date' => 'date',
    ];

    public function lease(): BelongsTo  { return $this->belongsTo(Lease::class); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
}
