<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, BelongsToAgency, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'subtotal'    => 'decimal:2',
        'tax_amount'  => 'decimal:2',
        'total'       => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'due_date'    => 'date',
        'issued_at'   => 'datetime',
        'paid_at'     => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->reference ??= 'INV-' . strtoupper(Str::random(8));
        });
    }

    public function lease(): BelongsTo { return $this->belongsTo(Lease::class); }
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function lineItems(): HasMany { return $this->hasMany(InvoiceLineItem::class); }

    public function getBalanceAttribute(): float
    {
        return (float) $this->total - (float) $this->amount_paid;
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date->isPast() && ! in_array($this->status, ['paid', 'void']);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'paid'         => 'success',
            'overdue'      => 'danger',
            'partially_paid' => 'warning',
            'sent'         => 'brand',
            'void'         => 'secondary',
            default        => 'secondary',
        };
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'sent', 'partially_paid']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue');
    }

    public function scopeForPeriod($query, int $month, int $year)
    {
        return $query->where('period_month', $month)->where('period_year', $year);
    }
}
