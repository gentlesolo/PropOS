<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Lease extends Model
{
    use HasFactory, BelongsToAgency, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'monthly_rent'        => 'decimal:2',
        'deposit_amount'      => 'decimal:2',
        'agency_fee'          => 'decimal:2',
        'legal_fee'           => 'decimal:2',
        'service_charge'      => 'decimal:2',
        'start_date'          => 'date',
        'end_date'            => 'date',
        'renewed_until'       => 'date',
        'deposit_refunded_at' => 'datetime',
        'deposit_deductions'  => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->reference ??= 'LSE-' . strtoupper(Str::random(8));
        });
    }

    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function listing(): BelongsTo { return $this->belongsTo(Listing::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'assigned_agent_id'); }
    public function contract(): BelongsTo { return $this->belongsTo(Contract::class); }
    public function rentPayments(): HasMany { return $this->hasMany(RentPayment::class); }

    public function getDaysUntilExpiryAttribute(): int
    {
        return (int) now()->diffInDays($this->end_date, false);
    }

    public function getPeriodMonthsAttribute(): int
    {
        return match($this->payment_frequency ?? 'monthly') {
            'quarterly' => 3,
            'bi_yearly' => 6,
            'yearly'    => 12,
            default     => 1,
        };
    }

    public function getPeriodRentAttribute(): float
    {
        return round((float) $this->monthly_rent * $this->periodMonths, 2);
    }

    public function getAnnualRentAttribute(): float
    {
        return round((float) $this->monthly_rent * 12, 2);
    }

    public function getPaymentFrequencyLabelAttribute(): string
    {
        return match($this->payment_frequency ?? 'monthly') {
            'quarterly' => 'Quarterly',
            'bi_yearly' => 'Bi-Yearly',
            'yearly'    => 'Yearly',
            default     => 'Monthly',
        };
    }

    public function getRentSuffixAttribute(): string
    {
        return match($this->payment_frequency ?? 'monthly') {
            'quarterly' => '/qtr',
            'bi_yearly' => '/6mo',
            'yearly'    => '/yr',
            default     => '/mo',
        };
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        // Yearly leases need 90-day notice; others use 60 days
        $threshold = ($this->payment_frequency ?? 'monthly') === 'yearly' ? 90 : 60;
        return $this->daysUntilExpiry > 0 && $this->daysUntilExpiry <= $threshold;
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return (float) $this->rentPayments()->whereIn('status', ['pending', 'overdue', 'partial'])->sum('amount_due')
            - (float) $this->rentPayments()->whereIn('status', ['partial'])->sum('amount_paid');
    }
}
