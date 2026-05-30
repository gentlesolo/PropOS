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
        'monthly_rent' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'renewed_until' => 'date',
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

    public function getIsExpiringSoonAttribute(): bool
    {
        return $this->daysUntilExpiry > 0 && $this->daysUntilExpiry <= 60;
    }

    public function getOutstandingBalanceAttribute(): float
    {
        return (float) $this->rentPayments()->whereIn('status', ['pending', 'overdue', 'partial'])->sum('amount_due')
            - (float) $this->rentPayments()->whereIn('status', ['partial'])->sum('amount_paid');
    }
}
