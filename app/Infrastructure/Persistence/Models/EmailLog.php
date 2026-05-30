<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    use HasFactory, BelongsToAgency;

    protected $guarded = ['id'];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    public function template(): BelongsTo { return $this->belongsTo(EmailTemplate::class, 'email_template_id'); }
    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function sentBy(): BelongsTo { return $this->belongsTo(User::class, 'sent_by'); }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'delivered', 'opened', 'clicked' => 'success',
            'bounced', 'failed' => 'danger',
            'sent' => 'brand',
            default => 'secondary',
        };
    }
}
