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

    public function graphics()
    {
        return $this->hasMany(\App\Infrastructure\Persistence\Models\ListingGraphic::class);
    }

    public function viewings()
    {
        return $this->hasMany(\App\Infrastructure\Persistence\Models\Viewing::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class)->orderByDesc('created_at');
    }

    public function inspections()
    {
        return $this->hasMany(Inspection::class)->orderByDesc('scheduled_at');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    public function leases()
    {
        return $this->hasMany(Lease::class)->orderByDesc('start_date');
    }

    public function activeLease()
    {
        return $this->hasOne(Lease::class)->where('status', 'active')->latest();
    }

    public function cmaReports()
    {
        return $this->hasMany(CmaReport::class)->orderByDesc('created_at');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class)->orderBy('due_at');
    }
}
