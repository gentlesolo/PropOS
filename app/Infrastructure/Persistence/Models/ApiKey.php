<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['token'];

    protected $casts = [
        'expires_at'    => 'datetime',
        'last_used_at'  => 'datetime',
    ];

    public static function generate(int $agencyId, string $name, string $type = 'public_read'): self
    {
        return self::create([
            'agency_id' => $agencyId,
            'name'      => $name,
            'token'     => Str::random(64),
            'type'      => $type,
        ]);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}
