<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Contract extends Model
{
    use HasFactory, BelongsToAgency, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'signatories' => 'array',
        'signed_at' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->reference ??= 'CON-' . strtoupper(Str::random(8));
        });
    }

    public function deal(): BelongsTo { return $this->belongsTo(Deal::class); }
    public function transaction(): BelongsTo { return $this->belongsTo(Transaction::class); }
    public function offer(): BelongsTo { return $this->belongsTo(Offer::class); }
    public function listing(): BelongsTo { return $this->belongsTo(Listing::class); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }

    public function getIsFullySignedAttribute(): bool
    {
        return $this->status === 'fully_signed';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'fully_signed' => 'success',
            'cancelled', 'expired' => 'danger',
            'signed_buyer', 'signed_seller' => 'warning',
            'sent', 'viewed' => 'brand',
            default => 'secondary',
        };
    }
}
