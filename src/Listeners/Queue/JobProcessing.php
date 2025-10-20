<?php

namespace Mattiverse\Userstamps\Listeners\Queue;

use Illuminate\Queue\Events\JobProcessing as JobProcessingEvent;
use Mattiverse\Userstamps\Actor;

class JobProcessing
{
    /**
     * Handle the event.
     *
     * Restores the actor ID from the job payload before the job is processed.
     * This ensures userstamps are correctly maintained in queued jobs.
     *
     * @param JobProcessingEvent $event
     * @return void
     */
    public function handle(JobProcessingEvent $event): void
    {
        $payload = $event->job->payload();

        Actor::set($payload['userstamps_actor_id'] ?? null);
    }
}
