<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RentPayment extends Model
{
    use HasFactory, BelongsToAgency;

    protected $guarded = ['id'];

    protected $casts = [
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'penalty' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->reference ??= 'PAY-' . strtoupper(Str::random(8));
        });
    }

    public function lease(): BelongsTo { return $this->belongsTo(Lease::class); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }

    public function getBalanceAttribute(): float
    {
        return (float) $this->amount_due - (float) ($this->amount_paid ?? 0);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date->isPast() && !in_array($this->status, ['paid', 'waived']);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'paid' => 'success',
            'overdue' => 'danger',
            'partial' => 'warning',
            'waived' => 'secondary',
            default => 'brand',
        };
    }
}
