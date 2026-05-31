<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallTranscript extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'speaker_segments' => 'array',
    ];

    public function call(): BelongsTo { return $this->belongsTo(Call::class); }
}
