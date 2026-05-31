<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Call extends Model
{
    use HasFactory, BelongsToAgency;

    protected $guarded = ['id'];

    protected $casts = [
        'consent_played'       => 'boolean',
        'flagged_for_coaching' => 'boolean',
        'started_at'           => 'datetime',
        'ended_at'             => 'datetime',
    ];

    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'agent_id'); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function transcript(): HasOne { return $this->hasOne(CallTranscript::class); }
    public function summary(): HasOne { return $this->hasOne(CallSummary::class); }

    public function getDurationFormattedAttribute(): string
    {
        if (! $this->duration_seconds) {
            return '0:00';
        }
        $mins = intdiv($this->duration_seconds, 60);
        $secs = $this->duration_seconds % 60;
        return "{$mins}:" . str_pad($secs, 2, '0', STR_PAD_LEFT);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
