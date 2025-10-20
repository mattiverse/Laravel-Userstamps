<?php

use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Mattiverse\Userstamps\Actor;
use Mattiverse\Userstamps\Listeners\Queue\JobProcessed as JobProcessedListener;
use Mattiverse\Userstamps\Listeners\Queue\JobProcessing as JobProcessingListener;
use Orchestra\Testbench\TestCase;

class ActorQueueEventsTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Mattiverse\Userstamps\UserstampsServiceProvider'];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Actor::clear();
    }

    protected function tearDown(): void
    {
        Actor::clear();
        parent::tearDown();
    }

    public function test_job_processing_listener_sets_actor_from_payload(): void
    {
        $job = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
        $job->method('payload')->willReturn([
            'userstamps_actor_id' => 42,
        ]);

        $event = new JobProcessing('redis', $job);
        $listener = new JobProcessingListener();
        $listener->handle($event);

        $this->assertEquals(42, Actor::id());
    }

    public function test_job_processing_listener_handles_missing_actor_id(): void
    {
        $job = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
        $job->method('payload')->willReturn([]);

        $event = new JobProcessing('redis', $job);
        $listener = new JobProcessingListener();
        $listener->handle($event);

        $this->assertNull(Actor::id());
    }

    public function test_job_processed_listener_clears_actor(): void
    {
        Actor::set(42);
        $this->assertEquals(42, Actor::id());

        $job = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
        $event = new JobProcessed('redis', $job);
        $listener = new JobProcessedListener();
        $listener->handle($event);

        $this->assertNull(Actor::id());
    }

    public function test_full_queue_lifecycle(): void
    {
        // Simulate job being queued with actor ID
        Actor::set(100);
        $actorIdWhenQueued = Actor::id();
        $this->assertEquals(100, $actorIdWhenQueued);

        // Simulate queue payload
        $payload = ['userstamps_actor_id' => $actorIdWhenQueued];

        // Clear (simulates end of web request)
        Actor::clear();
        $this->assertNull(Actor::id());

        // Simulate job starting (JobProcessing event)
        $job = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
        $job->method('payload')->willReturn($payload);
        $processingEvent = new JobProcessing('redis', $job);
        (new JobProcessingListener())->handle($processingEvent);

        // Actor should be restored
        $this->assertEquals(100, Actor::id());

        // Simulate job completion (JobProcessed event)
        $processedEvent = new JobProcessed('redis', $job);
        (new JobProcessedListener())->handle($processedEvent);

        // Actor should be cleared
        $this->assertNull(Actor::id());
    }
}
