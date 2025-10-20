<?php

use Mattiverse\Userstamps\Actor;
use Orchestra\Testbench\TestCase;

class ActorStressTest extends TestCase
{
    public function test_rapid_context_switching(): void
    {
        // Simulate 100 rapid job executions
        for ($i = 1; $i <= 100; $i++) {
            Actor::set($i);
            $this->assertEquals($i, Actor::id());
            Actor::clear();
            $this->assertNull(Actor::id());
        }
    }

    public function test_concurrent_like_behavior(): void
    {
        $values = [];

        // Simulate multiple "requests" (in reality, sequential in tests)
        for ($userId = 1; $userId <= 10; $userId++) {
            Actor::set($userId);
            $values[] = Actor::id();
            Actor::clear();
        }

        // Verify each got its own value
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], $values);
    }

    public function test_no_leakage_after_many_operations(): void
    {
        // Set and clear many times
        for ($i = 1; $i <= 1000; $i++) {
            Actor::set($i);
            Actor::clear();
        }

        // Should be clean
        $this->assertNull(Actor::id());

        // Should work normally after
        Actor::set(999);
        $this->assertEquals(999, Actor::id());
    }
}