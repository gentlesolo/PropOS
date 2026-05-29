<?php

namespace App\Infrastructure\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Portal extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'is_active',
        'base_url',
        'logo_path',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function syncs(): HasMany
    {
        return $this->hasMany(ListingPortalSync::class);
    }
}
