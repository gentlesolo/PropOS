<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use App\Infrastructure\Persistence\Scopes\BelongsToAgencyScope;

class ListingGraphic extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'post_copy' => 'array',
        'width'     => 'integer',
        'height'    => 'integer',
        'file_size' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAgencyScope);
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    public function getFormatLabelAttribute(): string
    {
        return match ($this->format) {
            'square'    => 'Instagram Square (1080×1080)',
            'landscape' => 'Facebook / LinkedIn (1200×630)',
            'story'     => 'Story / Reel (1080×1920)',
            default     => ucfirst($this->format),
        };
    }
}
