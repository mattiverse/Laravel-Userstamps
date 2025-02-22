<?php

namespace Mattiverse\Userstamps\Listeners;

use Illuminate\Database\Eloquent\Model;

class Restoring
{
    public function handle(Model $model): void
    {
        if (! $model->isUserstamping() || is_null($model->getDeletedByColumn())) {
            return;
        }

        $model->{$model->getDeletedByColumn()} = null;
    }
}
