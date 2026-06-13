<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentNumber extends Model
{
    use HasFactory, BelongsToAgency;

    protected $guarded = ['id'];

    protected $casts = [
        'active'      => 'boolean',
        'verified'    => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function calls(): HasMany { return $this->hasMany(Call::class, 'twilio_number', 'twilio_number'); }

    /**
     * The number to present to leads as caller ID.
     * For BYON numbers this is the agency's real number;
     * for platform-provisioned numbers it equals twilio_number.
     */
    public function getEffectiveDisplayNumber(): ?string
    {
        return $this->display_number ?? $this->twilio_number;
    }
}
