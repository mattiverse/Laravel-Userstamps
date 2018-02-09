<?php

namespace Wildside\Userstamps\Listeners;

class Restoring extends Controller {

    /**
     * When the model is being restored.
     *
     * @param Illuminate\Database\Eloquent\Model|static
     * @return void
     */
    public function handle($model)
    {
        if (! $this->prep($model)) {
            return;
        }

        $model -> {$this->deleted_by} = null;
    }
}
