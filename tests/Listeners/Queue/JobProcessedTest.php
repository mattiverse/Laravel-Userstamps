<?php

use Illuminate\Queue\Events\JobProcessed as JobProcessedEvent;
use Mattiverse\Userstamps\Actor;
use Mattiverse\Userstamps\Listeners\Queue\JobProcessed;
use Orchestra\Testbench\TestCase;

class JobProcessedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear actor state before each test
        Actor::clear();
    }

    public function test_handle_clears_actor(): void
    {
        // Set an actor first
        Actor::set(42);
        $this->assertEquals(42, Actor::id());

        $listener = new JobProcessed();

        $job = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
        $event = new JobProcessedEvent('connection', $job);

        $listener->handle($event);

        $this->assertNull(Actor::id());
    }

    public function test_handle_clears_actor_even_when_already_null(): void
    {
        // Actor is already null
        $this->assertNull(Actor::id());

        $listener = new JobProcessed();

        $job = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
        $event = new JobProcessedEvent('connection', $job);

        // Should not throw an error
        $listener->handle($event);

        $this->assertNull(Actor::id());
    }

    public function test_handle_is_called_with_proper_event_instance(): void
    {
        $listener = new JobProcessed();

        $job = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
        $event = new JobProcessedEvent('connection', $job);

        // Should accept the event without error
        $listener->handle($event);

        $this->assertTrue(true); // If we get here, it worked
    }

    public function test_handle_clears_various_actor_values(): void
    {
        $listener = new JobProcessed();
        $job = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
        $event = new JobProcessedEvent('connection', $job);

        $testIds = [1, 999, 12345, 0];

        foreach ($testIds as $testId) {
            Actor::set($testId);
            $this->assertEquals($testId, Actor::id());

            $listener->handle($event);

            $this->assertNull(Actor::id(), "Failed to clear actor for ID: {$testId}");
        }
    }

    public function test_handle_ensures_clean_state_for_next_job(): void
    {
        // Simulate job 1
        Actor::set(1);
        $this->assertEquals(1, Actor::id());

        $listener = new JobProcessed();
        $job = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
        $event = new JobProcessedEvent('connection', $job);

        $listener->handle($event);

        // Simulate job 2 - should start with clean state
        $this->assertNull(Actor::id());

        Actor::set(2);
        $this->assertEquals(2, Actor::id());

        $listener->handle($event);

        $this->assertNull(Actor::id());
    }
}
