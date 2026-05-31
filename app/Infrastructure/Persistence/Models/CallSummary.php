<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallSummary extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'key_points'         => 'array',
        'action_items'       => 'array',
        'agent_confirmed_at' => 'datetime',
        'agent_edited'       => 'boolean',
    ];

    public function call(): BelongsTo { return $this->belongsTo(Call::class); }

    public function getSentimentColorAttribute(): string
    {
        return match ($this->sentiment) {
            'hot'  => 'danger',
            'warm' => 'warning',
            'cold' => 'brand',
            default => 'secondary',
        };
    }
}
