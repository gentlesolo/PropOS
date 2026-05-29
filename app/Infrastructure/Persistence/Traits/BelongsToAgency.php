<?php

namespace App\Infrastructure\Persistence\Traits;

use App\Infrastructure\Persistence\Models\Agency;
use App\Infrastructure\Persistence\Scopes\BelongsToAgencyScope;
use App\Infrastructure\Tenancy\TenantResolver;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToAgency
{
    public static function bootBelongsToAgency(): void
    {
        static::addGlobalScope(new BelongsToAgencyScope);

        static::creating(function ($model) {
            if ($model->agency_id === null) {
                $resolver = app(TenantResolver::class);
                $model->agency_id = $resolver->getCurrentAgencyId();
            }
        });
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
