<?php

namespace Wildside\Userstamps\Listeners;

class Deleting extends Controller {

    /**
     * When the model is being deleted.
     *
     * @param Illuminate\Database\Eloquent\Model|static
     * @return void
     */
    public function handle($model)
    {
        if (! $this->prep($model)) {
            return;
        }

        $model -> {$this->deleted_by} = auth() -> id();
        $model -> save();
    }
}
