<?php

namespace Wildside\Userstamps\Listeners;

class Creating
{
    /**
     * When the model is being created.
     *
     * @param Illuminate\Database\Eloquent $model
     * @return void
     */
    public function handle($model)
    {
        if (! $model->isUserstamping()) {
            return;
        }

        if (is_null($model->{$model->getCreatedByColumn()})) {
            $model->{$model->getCreatedByColumn()} = auth()->id();
        }

        if (is_null($model->{$model->getUpdatedByColumn()}) && ! is_null($model->getUpdatedByColumn())) {
            $model->{$model->getUpdatedByColumn()} = auth()->id();
        }
    }
}
