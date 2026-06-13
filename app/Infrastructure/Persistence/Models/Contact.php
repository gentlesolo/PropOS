<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Infrastructure\Persistence\Scopes\BelongsToAgencyScope;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'intent_score' => 'integer',
        'preferences' => 'array',
        'tags' => 'array',
        'last_contacted_at' => 'datetime',
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
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function activities()
    {
        return $this->hasMany(ContactActivity::class)->orderByDesc('occurred_at');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class)->orderBy('due_at');
    }

    public function offers()
    {
        return $this->hasMany(Offer::class)->orderByDesc('created_at');
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class)->orderByDesc('created_at');
    }

    public function smsMessages()
    {
        return $this->hasMany(SmsMessage::class)->orderByDesc('created_at');
    }

    public function tenant()
    {
        return $this->hasOne(Tenant::class);
    }

    public function calls()
    {
        return $this->hasMany(Call::class)->orderByDesc('started_at');
    }

    public function latestCall()
    {
        return $this->hasOne(Call::class)->latestOfMany('started_at');
    }

    public function deals()
    {
        return $this->hasMany(Deal::class)->orderByDesc('created_at');
    }

    public function assignedAgent()
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
    }
}
