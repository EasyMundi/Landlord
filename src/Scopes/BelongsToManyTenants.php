<?php

namespace HipsterJazzbo\Landlord\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;

class BelongsToManyTenants implements Scope
{
    /** @var \Illuminate\Support\Collection */
    private $tenants;

    public function __construct(Collection $tenants)
    {
        $this->tenants = $tenants;
    }

    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        if ($this->tenants->isEmpty()) {
            return;
        }

        /** @var Model $tenantModel */
        $tenantModel = $model->getTenantModel();
        /** @var Model $tenantRelationsModel */
        $tenantRelationsModel = $model->getTenantRelationsModel();

        $builder->join($tenantRelationsModel->getTable(),
            function (JoinClause $join) use ($tenantModel, $tenantRelationsModel, $model) {

                $join
                    ->on(
                        "{$tenantRelationsModel->getTable()}.{$tenantRelationsModel->getForeignKey()}",
                        "=",
                        "{$model->getTable()}.{$model->getKeyName()}"
                    )
                    ->where(
                        "{$tenantRelationsModel->getTable()}.{$tenantRelationsModel->getTable()}_type",
                        '=',
                        $model->getMorphClass()
                    )
                    ->whereIn(
                        "{$tenantRelationsModel->getTable()}.{$tenantModel->getForeignKey()}",
                        $this->tenants
                    );
                if (method_exists($tenantRelationsModel, "forceDelete")) {
                    $join->whereNull("{$tenantRelationsModel->getTable()}.deleted_at");
                }
            }
        );

        $builder->join(
            $tenantModel->getTable(),
            function (JoinClause $join) use ($tenantModel, $tenantRelationsModel, $model) {
                $join->on(
                    "{$tenantModel->getTable()}.{$tenantModel->getKeyName()}",
                    "=",
                    "{$tenantRelationsModel->getTable()}.{$tenantModel->getForeignKey()}"
                );
                if (method_exists($tenantModel, "forceDelete")) {
                    $join->whereNull("{$tenantModel->getTable()}.deleted_at");
                }
            }
        );
    }
}
