# Test Suite Documentation

This document describes the test suite for the Laravel Userstamps queue support and Actor functionality.

## Test Files

### 1. `tests/ActorTest.php`
Unit tests for the `Actor` class.

**Tests:**
- `test_actor_id_returns_null_when_no_user_and_no_actor_set` - Verifies Actor returns null when nothing is set
- `test_actor_id_returns_auth_id_when_user_is_authenticated` - Verifies Actor returns Auth::id() when available
- `test_actor_set_stores_the_provided_id` - Tests manual actor ID storage
- `test_actor_id_prefers_auth_id_over_stored_actor_id` - Confirms Auth::id() takes precedence
- `test_actor_id_falls_back_to_stored_id_when_auth_returns_null` - Tests fallback mechanism
- `test_actor_clear_removes_stored_id` - Verifies clear() method works
- `test_actor_set_can_accept_null` - Tests setting null values
- `test_actor_set_overrides_previous_value` - Tests value replacement
- `test_actor_persists_across_multiple_calls` - Ensures state persistence
- `test_actor_works_with_string_ids` - Tests with various ID formats

**Coverage:**
- All public methods of the Actor class
- Fallback behavior
- State management

### 2. `tests/QueueUserstampsTest.php`
Integration tests for queue support functionality.

**Tests:**
- `test_queue_payload_includes_actor_id_when_user_is_authenticated` - Verifies payload creation
- `test_queue_payload_has_null_actor_id_when_no_user_authenticated` - Tests null user scenario
- `test_userstamps_are_maintained_in_queued_job` - End-to-end test for job creation
- `test_userstamps_are_maintained_in_queued_job_when_updating` - Tests updates in jobs
- `test_userstamps_are_maintained_in_queued_job_when_soft_deleting` - Tests soft deletes in jobs
- `test_actor_is_cleared_after_job_completes` - Verifies cleanup
- `test_multiple_queued_jobs_maintain_separate_contexts` - Tests isolation between jobs
- `test_actor_can_be_manually_set_for_console_commands` - Tests manual actor usage

**Coverage:**
- Queue payload creation
- Actor restoration in jobs
- Actor cleanup after jobs
- Model operations within jobs (create, update, delete)
- Context isolation between jobs

**Test Jobs:**
- `TestJob` - Empty job for payload testing
- `CreateModelJob` - Creates a model with userstamps
- `UpdateModelJob` - Updates a model with userstamps
- `DeleteModelJob` - Soft deletes a model with userstamps

### 3. `tests/Listeners/Queue/JobProcessingTest.php`
Unit tests for the `JobProcessing` listener.

**Tests:**
- `test_handle_sets_actor_from_job_payload` - Tests basic functionality
- `test_handle_sets_null_when_actor_id_not_in_payload` - Tests missing payload key
- `test_handle_sets_null_when_actor_id_is_null_in_payload` - Tests explicit null
- `test_handle_extracts_payload_from_event_job` - Verifies event handling
- `test_handle_overwrites_previous_actor_value` - Tests state replacement
- `test_handle_accepts_different_user_ids` - Tests various ID values

**Coverage:**
- Event handling
- Payload extraction
- Actor state management
- Edge cases (missing keys, null values)

### 4. `tests/Listeners/Queue/JobProcessedTest.php`
Unit tests for the `JobProcessed` listener.

**Tests:**
- `test_handle_clears_actor` - Tests basic cleanup functionality
- `test_handle_clears_actor_even_when_already_null` - Tests idempotency
- `test_handle_is_called_with_proper_event_instance` - Verifies event contract
- `test_handle_clears_various_actor_values` - Tests cleanup with different values
- `test_handle_ensures_clean_state_for_next_job` - Tests state isolation

**Coverage:**
- Actor cleanup
- Idempotency
- State isolation between jobs

## Running Tests

### Run All Tests
```bash
vendor/bin/phpunit
```

### Run Specific Test File
```bash
vendor/bin/phpunit tests/ActorTest.php
vendor/bin/phpunit tests/QueueUserstampsTest.php
vendor/bin/phpunit tests/Listeners/Queue/JobProcessingTest.php
vendor/bin/phpunit tests/Listeners/Queue/JobProcessedTest.php
```

### Run Specific Test Method
```bash
vendor/bin/phpunit --filter test_actor_id_returns_null_when_no_user_and_no_actor_set
```

### Run with Coverage (requires Xdebug or PCOV)
```bash
vendor/bin/phpunit --coverage-html coverage
```

## Test Structure

```
tests/
├── ActorTest.php                           # Actor class unit tests
├── QueueUserstampsTest.php                 # Queue integration tests
├── UserstampsTest.php                      # Existing userstamps tests
└── Listeners/
    └── Queue/
        ├── JobProcessingTest.php           # JobProcessing listener tests
        └── JobProcessedTest.php            # JobProcessed listener tests
```

## Testing Strategy

### Unit Tests
- Test individual classes in isolation
- Use mocking for dependencies
- Fast execution
- High code coverage

### Integration Tests
- Test complete workflows
- Use real Laravel components
- Verify end-to-end behavior
- Test with actual database operations

## Mocking

### Auth Facade
The `ActorTest` uses Auth facade mocking to avoid database dependencies:

```php
Auth::shouldReceive('id')->andReturn(1);
```

### Queue Jobs
The `JobProcessingTest` and `JobProcessedTest` use mock objects for queue jobs:

```php
$job = $this->createMock(\Illuminate\Contracts\Queue\Job::class);
$job->method('payload')->willReturn(['userstamps_actor_id' => 42]);
```

## Test Database

Integration tests use SQLite in-memory database:

```php
$app['config']->set('database.connections.testbench', [
    'driver' => 'sqlite',
    'database' => ':memory:',
]);
```

## Continuous Integration

These tests are designed to run in CI environments without special configuration:

- No external database required (uses SQLite in-memory)
- No Redis or other services needed (uses sync queue driver)
- Fast execution (under 1 second for most tests)

## Coverage Goals

- **Actor class**: 100% coverage
- **Queue listeners**: 100% coverage
- **Queue integration**: All major workflows covered
- **Edge cases**: Null values, missing data, state isolation

## Adding New Tests

When adding new functionality:

1. **Unit tests first**: Test individual methods in isolation
2. **Integration tests**: Test the complete workflow
3. **Edge cases**: Test null values, missing data, errors
4. **Backward compatibility**: Ensure existing tests still pass

## Known Limitations

- Queue tests use synchronous driver (can't test actual async behavior)
- Some tests require PHP PDO SQLite extension
- Tests assume PHP 8.2+ (matching package requirements)

## Troubleshooting

### "could not find driver" Error
Install PHP SQLite extension:
```bash
# Ubuntu/Debian
sudo apt-get install php-sqlite3

# macOS (with Homebrew)
brew install php
```

### "Class not found" Errors
Ensure dependencies are installed:
```bash
composer install
```

### Tests Failing Randomly
Ensure Actor state is cleared between tests:
```php
protected function setUp(): void
{
    parent::setUp();
    Actor::clear();
}
```
