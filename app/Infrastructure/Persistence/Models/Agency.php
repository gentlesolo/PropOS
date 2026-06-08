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
        'use_platform_branding',
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
        'ai_credits_balance',
        'ai_credits_allocated_monthly',
        'billing_cycle',
        'paystack_customer_code',
        'paystack_subscription_code',
    ];

    protected function casts(): array
    {
        return [
            'settings'              => 'array',
            'commission_splits'     => 'array',
            'default_commission_rate' => 'decimal:2',
            'use_platform_branding' => 'boolean',
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

    // --- Pricing & Subscriptions ---

    public function getPricingPlanAttribute(): array
    {
        $planId = $this->subscription_plan ?? 'solo';
        return config("pricing.plans.{$planId}") ?? config('pricing.plans.solo');
    }

    public function canAddAgent(): bool
    {
        $max = $this->pricing_plan['features']['max_agents'] ?? 1;
        if ($max === -1) return true;
        
        $current = $this->users()->count();
        return $current < $max;
    }

    public function canAddListing(): bool
    {
        $max = $this->pricing_plan['features']['max_listings'] ?? 15;
        if ($max === -1) return true;
        
        $current = \Illuminate\Support\Facades\DB::table('listings')->where('agency_id', $this->id)->where('status', 'active')->count();
        return $current < $max;
    }

    public function deductCredits(int $amount, string $reason): bool
    {
        if ($this->pricing_plan['ai_credits_monthly'] === -1) {
            return true; // Enterprise unlimited
        }

        if ($this->ai_credits_balance < $amount) {
            throw new \RuntimeException("Insufficient AI Credits. Please top up or upgrade your plan.");
        }
        
        $this->decrement('ai_credits_balance', $amount);
        
        \Illuminate\Support\Facades\DB::table('ai_usage_logs')->insert([
            'agency_id' => $this->id,
            'user_id' => auth()->id() ?? $this->users()->first()->id,
            'feature' => $reason,
            'credits_used' => $amount,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return true;
    }
}
