<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Offer extends Model
{
    use HasFactory, BelongsToAgency, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'counter_amount' => 'decimal:2',
        'expiry_date' => 'date',
        'proposed_occupation_date' => 'date',
        'responded_at' => 'datetime',
    ];

    public function deal(): BelongsTo { return $this->belongsTo(Deal::class); }
    public function listing(): BelongsTo { return $this->belongsTo(Listing::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function submittedBy(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
    public function contract(): HasOne { return $this->hasOne(Contract::class); }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast() && $this->status === 'pending';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'accepted' => 'success',
            'rejected', 'withdrawn' => 'danger',
            'countered' => 'warning',
            'expired' => 'secondary',
            default => 'brand',
        };
    }
}
