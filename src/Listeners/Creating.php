<?php

namespace Mattiverse\Userstamps\Listeners;

use Mattiverse\Userstamps\Userstamps;

class Creating
{
    /**
     * When the model is being created.
     *
     * @param  Illuminate\Database\Eloquent  $model
     * @return void
     */
    public function handle($model)
    {
        if (! $model->isUserstamping() || is_null($model->getCreatedByColumn())) {
            return;
        }

        if (is_null($model->{$model->getCreatedByColumn()})) {
            $model->{$model->getCreatedByColumn()} = Userstamps::getUserId();
        }

        if (is_null($model->{$model->getUpdatedByColumn()}) && ! is_null($model->getUpdatedByColumn())) {
            $model->{$model->getUpdatedByColumn()} = Userstamps::getUserId();
        }
    }
}
