<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Models\User;

class ComplianceReminder extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'due_date'        => 'date',
        'notified_at'     => 'datetime',
        'acknowledged_at' => 'datetime',
        'completed_at'    => 'datetime',
    ];

    public function agency(): BelongsTo    { return $this->belongsTo(Agency::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function related(): MorphTo     { return $this->morphTo(); }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['completed']);
    }

    public function scopeDueSoon(Builder $query, int $days = 7): Builder
    {
        return $query->whereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()])
            ->whereNotIn('status', ['completed']);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'acknowledged']);
    }

    public function getUrgencyAttribute(): string
    {
        if ($this->status === 'completed') return 'completed';
        if ($this->due_date->isPast()) return 'overdue';
        if ($this->due_date->diffInDays(now()) <= 7) return 'due_soon';
        if ($this->due_date->diffInDays(now()) <= 30) return 'upcoming';
        return 'future';
    }
}
