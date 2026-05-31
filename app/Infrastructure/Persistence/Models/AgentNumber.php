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
        'active' => 'boolean',
    ];

    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function calls(): HasMany { return $this->hasMany(Call::class, 'twilio_number', 'twilio_number'); }
}
