<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Infrastructure\Persistence\Models\MaintenanceRequest;

class Tenant extends Model
{
    use HasFactory, BelongsToAgency, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'monthly_income' => 'decimal:2',
        'references' => 'array',
        'fica_documents' => 'array',
        'fica_verified_at' => 'date',
    ];

    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function listing(): BelongsTo { return $this->belongsTo(Listing::class); }
    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'assigned_agent_id'); }
    public function leases(): HasMany { return $this->hasMany(Lease::class); }
    public function activeLease(): HasOne { return $this->hasOne(Lease::class)->where('status', 'active')->latest(); }
    public function maintenanceRequests(): HasMany { return $this->hasMany(MaintenanceRequest::class); }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'vacating', 'expiring_soon' => 'warning',
            'blacklisted' => 'danger',
            'vacated' => 'secondary',
            default => 'brand',
        };
    }
}
