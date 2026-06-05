<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailThread extends Model
{
    use HasFactory, BelongsToAgency;

    protected $guarded = ['id'];

    protected $casts = [
        'participants'    => 'array',
        'is_archived'     => 'boolean',
        'last_message_at' => 'datetime',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function emailAccount(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmailLog::class, 'thread_id')->orderBy('created_at');
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(EmailLog::class, 'thread_id')->latest()->limit(1);
    }

    public function incrementUnread(): void
    {
        $this->increment('unread_count');
        $this->update(['last_message_at' => now()]);
    }

    public function markRead(): void
    {
        $this->update(['unread_count' => 0]);
        $this->messages()->whereNull('read_at')->update(['read_at' => now()]);
    }
}
