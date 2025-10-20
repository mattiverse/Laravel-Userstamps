<?php

use Mattiverse\Userstamps\Actor;
use Orchestra\Testbench\TestCase;

class ActorQueueIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Simulate fresh container for each test (like a new request/job)
        $this->app = $this->createApplication();
        Actor::clear();
    }

    protected function tearDown(): void
    {
        Actor::clear();
        parent::tearDown();
    }

    public function test_actor_does_not_leak_between_simulated_jobs(): void
    {
        // Job 1
        Actor::set(10);
        $job1Value = Actor::id();
        $this->assertEquals(10, $job1Value);
        Actor::clear();

        // Job 2 - should NOT see Job 1's value
        $job2Value = Actor::id();
        $this->assertNull($job2Value);
        
        Actor::set(20);
        $this->assertEquals(20, Actor::id());
    }

    public function test_container_is_scoped_per_application_instance(): void
    {
        // Set in current app instance
        Actor::set(42);
        $this->assertEquals(42, Actor::id());

        // Create new app instance (simulates new request)
        $newApp = $this->createApplication();
        $this->app = $newApp;

        // Should be clean slate
        $this->assertNull(Actor::id());
    }
}