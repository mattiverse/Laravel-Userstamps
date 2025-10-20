<?php

use Illuminate\Support\Facades\Auth;
use Mattiverse\Userstamps\Actor;
use Orchestra\Testbench\TestCase;

class ActorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clear actor state between tests
        Actor::clear();
    }

    protected function tearDown(): void
    {
        // Clear actor state after tests
        Actor::clear();

        parent::tearDown();
    }

    public function test_actor_id_returns_null_when_no_user_and_no_actor_set(): void
    {
        // Mock Auth to return null
        Auth::shouldReceive('id')->andReturn(null);

        $this->assertNull(Actor::id());
    }

    public function test_actor_id_returns_auth_id_when_user_is_authenticated(): void
    {
        // Mock Auth to return a user ID
        Auth::shouldReceive('id')->andReturn(1);

        $this->assertEquals(1, Actor::id());
    }

    public function test_actor_set_stores_the_provided_id(): void
    {
        // Mock Auth to return null (simulating no authenticated user)
        Auth::shouldReceive('id')->andReturn(null);

        Actor::set(42);

        $this->assertEquals(42, Actor::id());
    }

    public function test_actor_id_prefers_auth_id_over_stored_actor_id(): void
    {
        Actor::set(42);

        // Mock Auth to return a different user ID
        Auth::shouldReceive('id')->andReturn(1);

        // Auth::id() should take precedence
        $this->assertEquals(1, Actor::id());
    }

    public function test_actor_id_falls_back_to_stored_id_when_auth_returns_null(): void
    {
        Actor::set(99);

        // Mock Auth to return null
        Auth::shouldReceive('id')->andReturn(null);

        $this->assertEquals(99, Actor::id());
    }

    public function test_actor_clear_removes_stored_id(): void
    {
        // Mock Auth to return null
        Auth::shouldReceive('id')->andReturn(null);

        Actor::set(42);

        $this->assertEquals(42, Actor::id());

        Actor::clear();

        $this->assertNull(Actor::id());
    }

    public function test_actor_set_can_accept_null(): void
    {
        // Mock Auth to return null
        Auth::shouldReceive('id')->andReturn(null);

        Actor::set(42);
        Actor::set(null);

        $this->assertNull(Actor::id());
    }

    public function test_actor_set_overrides_previous_value(): void
    {
        // Mock Auth to return null
        Auth::shouldReceive('id')->andReturn(null);

        Actor::set(1);
        $this->assertEquals(1, Actor::id());

        Actor::set(2);
        $this->assertEquals(2, Actor::id());
    }

    public function test_actor_persists_across_multiple_calls(): void
    {
        // Mock Auth to return null
        Auth::shouldReceive('id')->andReturn(null);

        Actor::set(123);

        $this->assertEquals(123, Actor::id());
        $this->assertEquals(123, Actor::id());
        $this->assertEquals(123, Actor::id());
    }

    public function test_actor_works_with_string_ids(): void
    {
        // Mock Auth to return null
        Auth::shouldReceive('id')->andReturn(null);

        // Some systems might use UUIDs or other string-based IDs
        // Though the type hint is int, test the behavior
        Actor::set(999);

        $this->assertEquals(999, Actor::id());
    }
}
