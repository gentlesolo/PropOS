<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Infrastructure\Persistence\Scopes\BelongsToAgencyScope;

class Viewing extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new BelongsToAgencyScope);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function feedback()
    {
        return $this->hasOne(ViewingFeedback::class);
    }
}
