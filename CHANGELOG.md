# Changelog

## [Unreleased]

### Added
- **Queue Support**: Automatic preservation of user context across queued jobs
  - User ID is automatically captured when jobs are dispatched
  - User context is restored when jobs are processed via dedicated listeners
  - No additional configuration or code required
  - New queue event listeners:
    - `Mattiverse\Userstamps\Listeners\Queue\JobProcessing` - Restores actor before job execution
    - `Mattiverse\Userstamps\Listeners\Queue\JobProcessed` - Clears actor after job execution
- **Actor Class**: New `Mattiverse\Userstamps\Actor` class for manual user context management
  - `Actor::set($id)` - Manually set user ID for operations
  - `Actor::id()` - Get current user ID (fallback to Auth::id())
  - `Actor::clear()` - Clear stored user ID
  - Useful for console commands, custom workers, and testing
- **Blueprint Macros for Dropping Columns**: 
  - `$table->dropUserstamps()` - Drop created_by and updated_by columns
  - `$table->dropUserstampSoftDeletes()` - Drop deleted_by column
- **Enhanced Documentation**: 
  - Queue support usage and examples
  - Manual actor management guide
  - Blueprint macro documentation
- **Comprehensive Test Suite**: 29 new tests covering all queue and Actor functionality
  - `ActorTest` - 10 unit tests for Actor class
  - `QueueUserstampsTest` - 8 integration tests for queue support
  - `JobProcessingTest` - 6 unit tests for JobProcessing listener
  - `JobProcessedTest` - 5 unit tests for JobProcessed listener
  - Full documentation in TESTING.md

### Changed
- User ID resolution now uses `Actor::id()` internally, providing better support for non-web contexts
- Service provider now registers queue event listeners automatically

### Technical Details
- Queue jobs now include `userstamps_actor_id` in their payload
- Queue event listeners (`JobProcessing`, `JobProcessed`) handle actor lifecycle via dedicated listener classes
- Listeners are automatically registered in the service provider
- Backwards compatible - existing code continues to work without changes

---

## Previous Versions

See GitHub releases for previous version history.
