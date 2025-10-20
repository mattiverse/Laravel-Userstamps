<?php

namespace Mattiverse\Userstamps;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class UserstampsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerBlueprintMacros();
        $this->registerQueueSupport();
    }

    protected function registerBlueprintMacros(): void
    {
        Blueprint::macro(
            'userstamps',
            function () {
                $this->foreignId('created_by')->nullable();
                $this->foreignId('updated_by')->nullable();
            }
        );

        Blueprint::macro(
            'userstampSoftDeletes',
            function () {
                $this->foreignId('deleted_by')->nullable();
            }
        );

        Blueprint::macro(
            'dropUserstamps',
            function () {
                $this->dropColumn('created_by', 'updated_by');
            }
        );

        Blueprint::macro(
            'dropUserstampSoftDeletes',
            function () {
                $this->dropColumn('deleted_by');
            }
        );
    }

    protected function registerQueueSupport(): void
    {
        // Add the current user ID into every queued job payload
        Queue::createPayloadUsing(fn ($connection, $queue, $payload) => [
            'userstamps_actor_id' => Actor::id(),
        ]);

        // Register queue event listeners
        Event::listen(
            JobProcessing::class,
            \Mattiverse\Userstamps\Listeners\Queue\JobProcessing::class
        );

        Event::listen(
            JobProcessed::class,
            \Mattiverse\Userstamps\Listeners\Queue\JobProcessed::class
        );
    }
}
