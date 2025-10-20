<?php

use Illuminate\Support\Facades\Auth;
use Mattiverse\Userstamps\Actor;
use Orchestra\Testbench\TestCase;

class ActorTest extends TestCase
{
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

    public function test_actor_can_set_and_get_user_id(): void
    {
        Actor::set(42);

        $this->assertEquals(42, Actor::id());
    }

    public function test_actor_returns_null_when_not_set_and_no_auth(): void
    {
        $this->assertNull(Actor::id());
    }

    public function test_actor_can_clear_stored_id(): void
    {
        Actor::set(42);
        Actor::clear();

        $this->assertNull(Actor::id());
    }

    public function test_actor_prefers_auth_id_over_stored_id(): void
    {
        // Set actor
        Actor::set(99);

        // Mock auth to return different ID
        Auth::shouldReceive('id')->andReturn(42);

        // Auth should take precedence
        $this->assertEquals(42, Actor::id());
    }

    public function test_actor_uses_stored_id_when_auth_returns_null(): void
    {
        Actor::set(99);
        Auth::shouldReceive('id')->andReturn(null);

        $this->assertEquals(99, Actor::id());
    }

    public function test_actor_set_null_removes_stored_id(): void
    {
        Actor::set(42);
        $this->assertEquals(42, Actor::id());

        Actor::set(null);
        $this->assertNull(Actor::id());
    }

    public function test_actor_isolation_between_test_runs(): void
    {
        // First run
        Actor::set(1);
        $this->assertEquals(1, Actor::id());

        // Clear (simulating end of request)
        Actor::clear();

        // Second run (simulating new request)
        Actor::set(2);
        $this->assertEquals(2, Actor::id());

        // Verify first value is gone
        Actor::clear();
        Actor::set(2);
        $this->assertEquals(2, Actor::id());
        $this->assertNotEquals(1, Actor::id());
    }

    public function test_multiple_sets_override_previous_value(): void
    {
        Actor::set(1);
        $this->assertEquals(1, Actor::id());

        Actor::set(2);
        $this->assertEquals(2, Actor::id());

        Actor::set(3);
        $this->assertEquals(3, Actor::id());
    }
}
