<?php

namespace Mattiverse\Userstamps;

class Userstamps
{
    /**
     * @var callable|null
     */
    protected static $resolveUsingCallback = null;

    public static function resolveUsing(callable $callback): void
    {
        static::$resolveUsingCallback = $callback;
    }

    public static function getUserId(): mixed
    {
        return is_null(static::$resolveUsingCallback) ? Actor::id() : (static::$resolveUsingCallback)();
    }
}
