<?php

namespace Mattiverse\Userstamps\Listeners;

use Mattiverse\Userstamps\Userstamps;

class Updating
{
    /**
     * When the model is being updated.
     *
     * @param  Illuminate\Database\Eloquent  $model
     * @return void
     */
    public function handle($model)
    {
        if (! $model->isUserstamping() || is_null($model->getUpdatedByColumn()) || is_null(Userstamps::getUserId())) {
            return;
        }

        $model->{$model->getUpdatedByColumn()} = Userstamps::getUserId();
    }
}
