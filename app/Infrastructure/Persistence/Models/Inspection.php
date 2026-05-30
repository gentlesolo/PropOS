<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inspection extends Model
{
    use HasFactory, BelongsToAgency;

    protected $guarded = ['id'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'findings' => 'array',
        'cost' => 'decimal:2',
    ];

    public function listing(): BelongsTo { return $this->belongsTo(Listing::class); }
    public function deal(): BelongsTo { return $this->belongsTo(Deal::class); }
    public function transaction(): BelongsTo { return $this->belongsTo(Transaction::class); }
    public function lease(): BelongsTo { return $this->belongsTo(Lease::class); }
    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'assigned_agent_id'); }

    public function getResultColorAttribute(): string
    {
        return match ($this->result) {
            'pass' => 'success',
            'pass_with_conditions' => 'warning',
            'fail' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'success',
            'in_progress' => 'warning',
            'cancelled' => 'danger',
            default => 'brand',
        };
    }
}
