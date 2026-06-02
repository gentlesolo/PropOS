<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\AgencyFactory::new();
    }

    protected $fillable = [
        'name',
        'slug',
        'custom_domain',
        'logo_path',
        'favicon_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'font_family',
        'border_radius',
        'sidebar_style',
        'custom_css',
        'tagline',
        'address',
        'phone',
        'email',
        'website',
        'timezone',
        'currency',
        'country_code',
        'subscription_plan',
        'subscription_status',
        'settings',
        'commission_splits',
        'default_commission_rate',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'commission_splits' => 'array',
            'default_commission_rate' => 'decimal:2',
        ];
    }

    public function getCurrencySymbolAttribute(): string
    {
        return self::symbolFor($this->currency ?? 'NGN');
    }

    public static function symbolFor(string $code): string
    {
        return match ($code) {
            'NGN' => '₦',
            'ZAR' => 'R',
            'GHS' => '₵',
            'KES' => 'KSh',
            'GBP' => '£',
            'EUR' => '€',
            default => '$',
        };
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function teamInvitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function webhookSubscriptions(): HasMany
    {
        return $this->hasMany(WebhookSubscription::class);
    }
}
