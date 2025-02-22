<?php

namespace Mattiverse\Userstamps;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UserstampsScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        return $builder;
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @return void
     */
    public function extend(Builder $builder)
    {
        $builder->macro('updateWithUserstamps', function (Builder $builder, $values) {
            if (! $builder->getModel()->isUserstamping() || is_null(Userstamps::getUserId())) {
                return $builder->update($values);
            }

            $values[$builder->getModel()->getUpdatedByColumn()] = Userstamps::getUserId();

            return $builder->update($values);
        });

        $builder->macro('deleteWithUserstamps', function (Builder $builder) {
            if (! $builder->getModel()->isUserstamping() || is_null(Userstamps::getUserId())) {
                return $builder->delete();
            }

            $builder->update([
                $builder->getModel()->getDeletedByColumn() => Userstamps::getUserId(),
            ]);

            return $builder->delete();
        });
    }
}
