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

        if (is_null($model -> {$this->created_by})) {
            $model -> {$this->created_by} = auth() -> id();
        }

        if (is_null($model -> {$this->updated_by})) {
            $model -> {$this->updated_by} = auth() -> id();
        }
    }
}
