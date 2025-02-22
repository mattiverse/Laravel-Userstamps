<?php

namespace Mattiverse\Userstamps;

use Illuminate\Support\Facades\Auth;

class Userstamps
{
    /**
     * @var callable|null
     */
    protected static $resolveUsingCallback = null;

    /**
     * Register a callback that is responsible for resolving the user.
     */
    public static function resolveUsing(callable $callback): void
    {
        static::$resolveUsingCallback = $callback;
    }

    public static function getUserId(): mixed
    {
        return is_null(static::$resolveUsingCallback) ? Auth::id() : (static::$resolveUsingCallback)();
    }
}
