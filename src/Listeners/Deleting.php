<?php

namespace Wildside\Userstamps\Listeners;

class Deleting {

    /**
     * When the model is being deleted.
     *
     * @param Illuminate\Database\Eloquent $model
     * @return void
     */
    public function handle($model)
    {
        if (! $model -> isUserstamping()) {
            return;
        }

        if (is_null($model -> {$model -> getDeletedByColumn()})) {
            $model -> {$model -> getDeletedByColumn()} = auth() -> id();
        }

        $dispatcher = $model -> getEventDispatcher();

        $model -> unsetEventDispatcher();

        $model -> save();

        $model -> setEventDispatcher($dispatcher);
    }
}
