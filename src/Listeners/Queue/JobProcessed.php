<?php

namespace Mattiverse\Userstamps\Listeners\Queue;

use Illuminate\Queue\Events\JobProcessed as JobProcessedEvent;
use Mattiverse\Userstamps\Actor;

class JobProcessed
{
    /**
     * Handle the event.
     *
     * Clears the actor ID after the job has been processed.
     * This ensures the actor context doesn't leak between jobs.
     */
    public function handle(JobProcessedEvent $event): void
    {
        Actor::clear();
    }
}
