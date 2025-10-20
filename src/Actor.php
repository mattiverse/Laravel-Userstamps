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
     * Container key for storing actor ID
     */
    private const CONTAINER_KEY = 'userstamps.actor_id';

    /**
     * Set the actor ID to be used when Auth::id() is not available.
     *
     * @param  int|null  $id
     * @return void
     */
    public static function set(?int $id): void
    {
        if ($id === null) {
            app()->forgetInstance(self::CONTAINER_KEY);
        } else {
            app()->instance(self::CONTAINER_KEY, $id);
        }
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
        // Always prefer Auth::id() first
        if ($authId = Auth::id()) {
            return $authId;
        }

        // Fall back to container value
        return app()->has(self::CONTAINER_KEY) 
            ? app()->make(self::CONTAINER_KEY) 
            : null;
    }

    /**
     * Clear the stored actor ID.
     *
     * @return void
     */
    public static function clear(): void
    {
        app()->forgetInstance(self::CONTAINER_KEY);
    }
}
