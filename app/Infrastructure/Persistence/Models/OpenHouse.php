<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Infrastructure\Persistence\Scopes\BelongsToAgencyScope;
use Illuminate\Support\Str;

class OpenHouse extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'starts_at'        => 'datetime',
        'ends_at'          => 'datetime',
        'rsvp_count'       => 'integer',
        'attendance_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new BelongsToAgencyScope);

        static::creating(function (self $openHouse) {
            $openHouse->rsvp_slug ??= Str::random(12);
        });
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function rsvps()
    {
        return $this->hasMany(OpenHouseRsvp::class);
    }

    public function getDurationMinutesAttribute(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at);
    }
}
