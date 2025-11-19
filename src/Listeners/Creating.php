<?php

namespace Mattiverse\Userstamps\Listeners;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Userstamps;

class Creating
{
    public function handle(Model $model): void
    {
        if (! $model->isUserstamping() || is_null($model->getCreatedByColumn())) {
            return;
        }

        if (is_null($model->{$model->getCreatedByColumn()})) {
            $model->{$model->getCreatedByColumn()} = Userstamps::getUserId();
        }

        if ($model->isFillingUpdatedByOnCreate()) {
            if (! is_null($model->getUpdatedByColumn()) && is_null($model->{$model->getUpdatedByColumn()})) {
                $model->{$model->getUpdatedByColumn()} = Userstamps::getUserId();
            }
        }
    }
}
