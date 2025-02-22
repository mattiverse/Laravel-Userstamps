<?php

namespace Mattiverse\Userstamps;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UserstampsScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        //
    }

    public function extend(Builder $builder): void
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
