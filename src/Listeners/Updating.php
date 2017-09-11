<?php

namespace Wildside\Userstamps\Listeners;

class Updating extends Controller {

    /**
     * When the model is being updated.
     *
     * @param Illuminate\Database\Eloquent\Model|static
     * @return void
     */
    public function handle($model)
    {
        if (! $this->prep($model)) {
            return;
        }

        $model -> {$this->updated_by} = auth() -> id();
    }
}
