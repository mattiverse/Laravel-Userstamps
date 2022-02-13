<?php

namespace Wildside\Userstamps\Listeners;

class Restoring
{
    /**
     * When the model is being restored.
     *
     * @param  Illuminate\Database\Eloquent  $model
     * @return void
     */
    public function handle($model)
    {
        if (! $model->isUserstamping() || is_null($model->getDeletedByColumn())) {
            return;
        }

        $model->{$model->getDeletedByColumn()} = null;
    }
}
