<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUpStep extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'delay_days' => 'integer',
        'step_number' => 'integer',
        'executed_at' => 'datetime',
    ];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(FollowUpSequence::class);
    }
}
