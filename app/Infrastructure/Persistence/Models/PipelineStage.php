<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Infrastructure\Persistence\Scopes\BelongsToAgencyScope;

class PipelineStage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'is_default' => 'boolean',
        'is_won' => 'boolean',
        'is_lost' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new BelongsToAgencyScope);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function deals()
    {
        return $this->hasMany(Deal::class)->orderBy('updated_at', 'desc');
    }
}
