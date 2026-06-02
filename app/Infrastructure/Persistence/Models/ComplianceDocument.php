<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplianceDocument extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_fica_required' => 'boolean',
        'expiry_date' => 'date',
        'reviewed_at' => 'datetime',
        'file_size' => 'integer',
    ];

    protected $appends = ['expiry_status'];

    public function transaction(): BelongsTo { return $this->belongsTo(Transaction::class); }
    public function lease(): BelongsTo      { return $this->belongsTo(Lease::class); }
    public function listing(): BelongsTo    { return $this->belongsTo(Listing::class); }
    public function property(): BelongsTo   { return $this->belongsTo(Property::class); }
    public function uploadedBy(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }

    public function getExpiryStatusAttribute(): string
    {
        if (! $this->expiry_date) return 'none';
        if ($this->expiry_date->isPast()) return 'expired';
        if ($this->expiry_date->diffInDays(now()) <= 30) return 'expiring_soon';
        return 'valid';
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')->where('expiry_date', '<', now());
    }

    public function getUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'success',
            'rejected' => 'danger',
            'under_review' => 'warning',
            'uploaded' => 'info',
            default => 'slate',
        };
    }
}
