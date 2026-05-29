<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Infrastructure\Persistence\Scopes\BelongsToAgencyScope;

class Listing extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'listing_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'mandate_start_date' => 'date',
        'mandate_end_date' => 'date',
        'features_highlighted' => 'array',
        'days_on_market' => 'integer',
        'health_score' => 'integer',
        'portal_ids' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new BelongsToAgencyScope);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function media()
    {
        return $this->hasMany(ListingMedia::class)->orderBy('order');
    }

    public function coverPhoto()
    {
        return $this->hasOne(ListingMedia::class)->where('is_cover', true)->where('file_type', 'image');
    }

    public function portalSyncs()
    {
        return $this->hasMany(ListingPortalSync::class);
    }
}
