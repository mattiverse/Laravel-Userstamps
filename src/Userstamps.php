<?php

namespace Wildside\Userstamps;

trait Userstamps
{
    /**
     * Whether we're currently maintaing userstamps.
     *
     * @param bool
     */
    protected $userstamping = true;

    /**
     * Boot the userstamps trait for a model.
     *
     * @return void
     */
    public static function bootUserstamps()
    {
        static::addGlobalScope(new UserstampsScope);

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
        return $this->belongsTo($this->getUserClass(), $this->getCreatedByColumn(), $this->getCreatedByKey());
    }

    /**
     * Get the user that edited the model.
     */
    public function editor()
    {
        return $this->belongsTo($this->getUserClass(), $this->getUpdatedByColumn(), $this->getUpdatedByKey());
    }

    /**
     * Get the user that deleted the model.
     */
    public function destroyer()
    {
        return $this->belongsTo($this->getUserClass(), $this->getDeletedByColumn(), $this->getDeletedByKey());
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
     * Get the name of the foreign "id" column for the creator.
     *
     * @return string
     */
    public function getCreatedByKey()
    {
        return defined('static::CREATED_BY_KEY') ? static::CREATED_BY_KEY : (new $this->getUserClass())->getKeyName();
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
     *  Get the name of the foreign "id" column for the editor.
     *
     * @return string
     */
    public function getUpdatedByKey()
    {
        return defined('static::UPDATED_BY_KEY') ? static::UPDATED_BY_KEY : (new $this->getUserClass())->getKeyName();
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
     *  Get the name of the foreign "id" column for the destroyer.
     *
     * @return string
     */
    public function getDeletedByKey()
    {
        return defined('static::DELETED_BY_KEY') ? static::DELETED_BY_KEY : (new $this->getUserClass())->getKeyName();
    }

    /**
     * Check if we're maintaing Userstamps on the model.
     *
     * @return bool
     */
    public function isUserstamping()
    {
        return $this->userstamping;
    }

    /**
     * Stop maintaining Userstamps on the model.
     *
     * @return void
     */
    public function stopUserstamping()
    {
        $this->userstamping = false;
    }

    /**
     * Start maintaining Userstamps on the model.
     *
     * @return void
     */
    public function startUserstamping()
    {
        $this->userstamping = true;
    }

    /**
     * Get the class being used to provide a User.
     *
     * @return string
     */
    protected function getUserClass()
    {
        return config('auth.providers.users.model');
    }
}
