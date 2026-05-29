<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViewingFeedback extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'overall_rating' => 'integer',
        'price_perception' => 'integer',
        'would_make_offer' => 'boolean',
    ];

    public function viewing(): BelongsTo
    {
        return $this->belongsTo(Viewing::class);
    }
}
