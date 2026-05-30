<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    use HasFactory, BelongsToAgency;

    protected $guarded = ['id'];

    protected $casts = [
        'cost' => 'decimal:4',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function sentBy(): BelongsTo { return $this->belongsTo(User::class, 'sent_by'); }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'delivered' => 'success',
            'failed', 'undelivered' => 'danger',
            'sent' => 'brand',
            default => 'secondary',
        };
    }
}
