<?php

use Illuminate\Queue\Events\JobProcessing as JobProcessingEvent;
use Mattiverse\Userstamps\Actor;
use Mattiverse\Userstamps\Listeners\Queue\JobProcessing;
use Orchestra\Testbench\TestCase;

class JobProcessingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear actor state before each test
        Actor::clear();
    }

    public function test_handle_sets_actor_from_job_payload(): void
    {
        $listener = new JobProcessing;

        $job = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
        $job->method('payload')->willReturn([
            'userstamps_actor_id' => 42,
        ]);

        $event = new JobProcessingEvent('connection', $job);

        $listener->handle($event);

        $this->assertEquals(42, Actor::id());
    }

    public function test_handle_sets_null_when_actor_id_not_in_payload(): void
    {
        // First set an actor to ensure it gets overwritten
        Actor::set(99);

        $listener = new JobProcessing;

        $job = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
        $job->method('payload')->willReturn([
            'other_data' => 'value',
        ]);

        $event = new JobProcessingEvent('connection', $job);

        $listener->handle($event);

        $this->assertNull(Actor::id());
    }

    public function test_handle_sets_null_when_actor_id_is_null_in_payload(): void
    {
        // First set an actor
        Actor::set(99);

        $listener = new JobProcessing;

        $job = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
        $job->method('payload')->willReturn([
            'userstamps_actor_id' => null,
        ]);

        $event = new JobProcessingEvent('connection', $job);

        $listener->handle($event);

        $this->assertNull(Actor::id());
    }

    public function test_handle_extracts_payload_from_event_job(): void
    {
        $listener = new JobProcessing;

        $job = $this->createMock(\Illuminate\Contracts\Queue\Job::class);

        // Ensure the payload method is called
        $job->expects($this->once())
            ->method('payload')
            ->willReturn(['userstamps_actor_id' => 123]);

        $event = new JobProcessingEvent('connection', $job);

        $listener->handle($event);

        $this->assertEquals(123, Actor::id());
    }

    public function test_handle_overwrites_previous_actor_value(): void
    {
        Actor::set(1);

        $listener = new JobProcessing;

        $job = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
        $job->method('payload')->willReturn([
            'userstamps_actor_id' => 2,
        ]);

        $event = new JobProcessingEvent('connection', $job);

        $listener->handle($event);

        $this->assertEquals(2, Actor::id());
    }

    public function test_handle_accepts_different_user_ids(): void
    {
        $listener = new JobProcessing;

        $testIds = [1, 999, 12345, 0];

        foreach ($testIds as $testId) {
            $job = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
            $job->method('payload')->willReturn([
                'userstamps_actor_id' => $testId,
            ]);

            $event = new JobProcessingEvent('connection', $job);

            $listener->handle($event);

            $this->assertEquals($testId, Actor::id(), "Failed for ID: {$testId}");
        }
    }
}
