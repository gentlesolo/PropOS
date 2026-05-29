<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

class OpenHouseRsvp extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'checked_in'    => 'boolean',
        'reminder_sent' => 'boolean',
        'checked_in_at' => 'datetime',
    ];

    public function openHouse()
    {
        return $this->belongsTo(OpenHouse::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
