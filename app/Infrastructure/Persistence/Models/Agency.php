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
        'primary_color',
        'secondary_color',
        'accent_color',
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
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function teamInvitations(): HasMany
    {
        return $this->hasMany(TeamInvitation::class);
    }
}
