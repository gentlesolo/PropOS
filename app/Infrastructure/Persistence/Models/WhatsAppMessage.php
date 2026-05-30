<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessage extends Model
{
    use HasFactory, BelongsToAgency;

    protected $table = 'whatsapp_messages';

    protected $guarded = ['id'];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function contact(): BelongsTo { return $this->belongsTo(Contact::class); }
    public function template(): BelongsTo { return $this->belongsTo(WhatsAppTemplate::class, 'template_id'); }
}
