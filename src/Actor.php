<?php

namespace Mattiverse\Userstamps;

use Illuminate\Support\Facades\Auth;

/**
 * Actor handles user ID resolution with support for queue job contexts.
 * 
 * This class provides a fallback mechanism to maintain user context
 * when processing queued jobs, where the authenticated user may not
 * be available through the standard Auth facade.
 */
final class Actor
{
    /**
     * The stored actor ID, typically set when processing queued jobs.
     *
     * @var int|null
     */
    private static ?int $id = null;

    /**
     * Set the actor ID to be used when Auth::id() is not available.
     *
     * @param int|null $id
     * @return void
     */
    public static function set(?int $id): void
    {
        self::$id = $id;
    }

    /**
     * Get the current actor ID.
     * 
     * Returns the authenticated user ID if available, 
     * otherwise returns the stored actor ID.
     *
     * @return int|null
     */
    public static function id(): ?int
    {
        return Auth::id() ?? self::$id;
    }

    /**
     * Clear the stored actor ID.
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$id = null;
    }
}
