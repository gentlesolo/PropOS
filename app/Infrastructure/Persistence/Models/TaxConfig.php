<?php

namespace App\Infrastructure\Persistence\Models;

use App\Infrastructure\Persistence\Traits\BelongsToAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaxConfig extends Model
{
    use HasFactory, BelongsToAgency;

    protected $guarded = ['id'];

    protected $casts = [
        'rate'       => 'decimal:2',
        'is_default' => 'boolean',
        'is_active'  => 'boolean',
    ];

    public function scopeActive($query)   { return $query->where('is_active', true); }
    public function scopeDefault($query)  { return $query->where('is_default', true); }

    public static function getApplicableRate(int $agencyId, string $propertyType = 'residential'): float
    {
        $config = static::where('agency_id', $agencyId)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('applies_to', $propertyType)->orWhere('applies_to', 'all'))
            ->orderByDesc('is_default')
            ->first();

        return $config ? (float) $config->rate : 0.0;
    }
}
