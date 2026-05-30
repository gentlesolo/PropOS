<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Infrastructure\Persistence\Scopes\BelongsToAgencyScope;

class Deal extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'value' => 'decimal:2',
        'momentum_score' => 'integer',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new BelongsToAgencyScope);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function stage()
    {
        return $this->belongsTo(PipelineStage::class, 'pipeline_stage_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function listing()
    {
        return $this->belongsTo(Listing::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function activities()
    {
        return $this->hasMany(ContactActivity::class)->orderByDesc('occurred_at');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class)->orderByDesc('created_at');
    }

    public function acceptedOffer()
    {
        return $this->hasOne(Offer::class)->where('status', 'accepted');
    }

    public function contract()
    {
        return $this->hasOne(Contract::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class)->orderBy('due_at');
    }

    public function inspections()
    {
        return $this->hasMany(Inspection::class)->orderByDesc('scheduled_at');
    }

    public function checklistItems()
    {
        return $this->hasMany(StageChecklistItem::class)->orderBy('order');
    }

    public function currentStageChecklist()
    {
        return $this->hasMany(StageChecklistItem::class)
            ->where('pipeline_stage_id', $this->pipeline_stage_id)
            ->orderBy('order');
    }

    public function getMomentumLabelAttribute(): string
    {
        return match (true) {
            $this->momentum_score >= 70 => 'Hot',
            $this->momentum_score >= 40 => 'Warm',
            default => 'Cold',
        };
    }
}
