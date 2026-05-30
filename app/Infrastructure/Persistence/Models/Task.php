<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory, BelongsToAgency, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function assignedTo(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function deal(): BelongsTo { return $this->belongsTo(Deal::class); }
    public function listing(): BelongsTo { return $this->belongsTo(Listing::class); }
    public function transaction(): BelongsTo { return $this->belongsTo(Transaction::class); }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_at && $this->due_at->isPast() && !in_array($this->status, ['completed', 'cancelled']);
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'medium' => 'brand',
            default => 'secondary',
        };
    }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeOverdue($query) { return $query->where('due_at', '<', now())->whereNotIn('status', ['completed', 'cancelled']); }
    public function scopeForUser($query, int $userId) { return $query->where('assigned_to', $userId); }
}
