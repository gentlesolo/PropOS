<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    use HasFactory, BelongsToAgency;

    protected $guarded = ['id'];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'gross_commission' => 'decimal:2',
        'agent_split_percentage' => 'decimal:2',
        'agent_commission' => 'decimal:2',
        'agency_commission' => 'decimal:2',
        'expected_payment_date' => 'date',
        'paid_at' => 'date',
    ];

    public function transaction(): BelongsTo { return $this->belongsTo(Transaction::class); }
    public function deal(): BelongsTo { return $this->belongsTo(Deal::class); }
    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'agent_id'); }
}
