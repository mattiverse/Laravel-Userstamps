<?php

namespace Wildside\Userstamps\Listeners;

class Controller {
    
    // Default column names
    public $created_by = 'created_by';
    public $updated_by = 'updated_by';
    public $deleted_by = 'deleted_by';

    /**
     * When the model is being created.
     *
     * @param Illuminate\Database\Eloquent\Model|static
     * @return bool
     */
    public function prep($model)
    {
        if (! $model -> isUserstamping()) {
            return false;
        }
    
        // Get custom column names from callee class if set
        if (defined(get_class($model).'::CREATED_BY')) $this->created_by = $model::CREATED_BY;
        if (defined(get_class($model).'::UPDATED_BY')) $this->updated_by = $model::UPDATED_BY;
        if (defined(get_class($model).'::DELETED_BY')) $this->deleted_by = $model::DELETED_BY;
        
        return true;
    }
}
