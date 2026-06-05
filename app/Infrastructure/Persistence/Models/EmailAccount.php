<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class EmailAccount extends Model
{
    use HasFactory, BelongsToAgency;

    protected $guarded = ['id'];

    protected $hidden = ['password'];

    protected $casts = [
        'is_shared'      => 'boolean',
        'is_default'     => 'boolean',
        'is_active'      => 'boolean',
        'last_synced_at' => 'datetime',
        'imap_port'      => 'integer',
        'smtp_port'      => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function threads(): HasMany
    {
        return $this->hasMany(EmailThread::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = Crypt::encryptString($value);
    }

    public function getPasswordAttribute(?string $value): ?string
    {
        if ($value === null) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return null;
        }
    }

    public function setUsernameAttribute(string $value): void
    {
        $this->attributes['username'] = Crypt::encryptString($value);
    }

    public function getUsernameAttribute(?string $value): ?string
    {
        if ($value === null) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception) {
            return null;
        }
    }

    public function getImapConnectionStringAttribute(): string
    {
        $enc = match ($this->imap_encryption) {
            'ssl'  => '/ssl',
            'none' => '/notls',
            default => '',
        };
        return "{{$this->imap_host}:{$this->imap_port}/imap{$enc}}";
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->email_address;
    }
}
