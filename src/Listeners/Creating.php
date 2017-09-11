<?php

namespace Wildside\Userstamps\Listeners;

class Creating extends Controller {

    /**
     * When the model is being created.
     *
     * @param Illuminate\Database\Eloquent\Model|static
     * @return void
     */
    public function handle($model)
    {
        if (! $this->prep($model)) {
            return;
        }

        $model -> {$this->created_by} = auth() -> id();
        $model -> {$this->updated_by} = auth() -> id();
    }
}
