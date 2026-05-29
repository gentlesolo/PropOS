<?php

namespace App\Infrastructure\Persistence\Scopes;

use App\Infrastructure\Tenancy\TenantResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BelongsToAgencyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $resolver = app(TenantResolver::class);
        $agencyId = $resolver->getCurrentAgencyId();

        if ($agencyId !== null) {
            $builder->where($model->getTable() . '.agency_id', $agencyId);
        }
    }
}
