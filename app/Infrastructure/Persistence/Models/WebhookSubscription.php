<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WebhookSubscription extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $hidden = ['secret'];

    protected $casts = [
        'events'            => 'array',
        'is_active'         => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    public static function register(int $agencyId, string $url, array $events): self
    {
        return self::create([
            'agency_id' => $agencyId,
            'url'       => $url,
            'secret'    => Str::random(64),
            'events'    => $events,
        ]);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    /** Generate HMAC-SHA256 signature for a payload. */
    public function sign(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret);
    }
}
