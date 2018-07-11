<?php

namespace Wildside\Userstamps;

trait Userstamps {

    /**
     * Whether we're currently maintaing userstamps.
     *
     * @param bool
     */
    protected $userstamping = true;

    /**
     * Whether we should sync status with timestamps.
     *
     * @param bool
     */
    protected $syncUserWithTime = true;

    /**
     * Boot the userstamps trait for a model.
     *
     * @return void
     */
    public static function bootUserstamps()
    {
        static::registerListeners();
    }

    /**
     * Register events we need to listen for.
     *
     * @return void
     */
    public static function registerListeners()
    {
        static::creating('Wildside\Userstamps\Listeners\Creating@handle');
        static::updating('Wildside\Userstamps\Listeners\Updating@handle');

        if (static::usingSoftDeletes()) {
            static::deleting('Wildside\Userstamps\Listeners\Deleting@handle');
            static::restoring('Wildside\Userstamps\Listeners\Restoring@handle');
        }
    }

    /**
     * Has the model loaded the SoftDeletes trait.
     *
     * @return bool
     */
    public static function usingSoftDeletes()
    {
        static $usingSoftDeletes;

        if (is_null($usingSoftDeletes)) {
            return $usingSoftDeletes = in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(get_called_class()));
        }

        return $usingSoftDeletes;
    }

    /**
     * Get the user that created the model.
     */
    public function creator()
    {
        return $this -> belongsTo($this -> getUserClass(), 'created_by');
    }

    /**
     * Get the user that edited the model.
     */
    public function editor()
    {
        return $this -> belongsTo($this -> getUserClass(), 'updated_by');
    }

    /**
     * Get the user that deleted the model.
     */
    public function destroyer()
    {
        return $this -> belongsTo($this -> getUserClass(), 'deleted_by');
    }

    /**
     * Get the name of the "created by" column.
     *
     * @return string
     */
    public function getCreatedByColumn()
    {
        return defined('static::CREATED_BY') ? static::CREATED_BY : 'created_by';
    }

    /**
     * Get the name of the "updated by" column.
     *
     * @return string
     */
    public function getUpdatedByColumn()
    {
        return defined('static::UPDATED_BY') ? static::UPDATED_BY : 'updated_by';
    }

    /**
     * Get the name of the "deleted by" column.
     *
     * @return string
     */
    public function getDeletedByColumn()
    {
        return defined('static::DELETED_BY') ? static::DELETED_BY : 'deleted_by';
    }

    /**
     * Check if we're maintaing Userstamps on the model. Use Timestamps value if we are syncronizing and update to reflect
     *
     * @return bool
     */
    public function isUserstamping()
    {
        if ($this -> syncUserWithTime) {
            $this -> userstamping = $this -> timestamps;
        }
 
        return $this -> userstamping;
    }

    /**
     * Stop maintaining Userstamps on the model and update Timestamps if we are syncronizing.
     *
     * @return void
     */
    public function stopUserstamping()
    {
        $this -> userstamping = false;

        if ($this -> syncUserWithTime) {
            $this -> timestamps = false;
        }
    }

    /**
     * Start maintaining Userstamps on the model and update Timestamps if we are syncronizing.
     *
     * @return void
     */
    public function startUserstamping()
    {
        $this -> userstamping = true;

        if ($this -> syncUserWithTime) {
            $this -> timestamps = true;
        }
    }

    /**
     * Stop maintaining Timestamps on the model and update Userstamps if we are syncronizing.
     *
     * @return void
     */
    public function stopTimestamping()
    {
        $this -> timestamps = false;

        if ($this -> syncUserWithTime) {
            $this -> userstamping = false;
        }
    }

    /**
     * Start maintaining Timestamps on the model and update Userstamps if we are syncronizing.
     *
     * @return void
     */
    public function startTimestamping()
    {
        $this -> timestamps = true;

        if ($this -> syncUserWithTime) {
            $this -> userstamping = true;
        }
    }

    /**
     * Get the class being used to provide a User.
     *
     * @return string
     */
    protected function getUserClass()
    {
        if (get_class(auth()) === 'Illuminate\Auth\Guard') {
            return auth() -> getProvider() -> getModel();
        }

        return auth() -> guard() -> getProvider() -> getModel();
    }
}
