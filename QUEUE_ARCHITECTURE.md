# Queue Listener Architecture

## Structure

```
src/
├── Listeners/
│   ├── Creating.php          # Model created event
│   ├── Updating.php          # Model updated event
│   ├── Deleting.php          # Model deleted event
│   ├── Restoring.php         # Model restored event
│   └── Queue/
│       ├── JobProcessing.php # Before queue job execution
│       └── JobProcessed.php  # After queue job execution
```

## Event Flow

### Normal HTTP Request
```
HTTP Request
    ↓
[Authenticated User]
    ↓
Actor::id() → returns Auth::id()
    ↓
Model Event (creating/updating/deleting)
    ↓
Listener sets created_by/updated_by/deleted_by
    ↓
Model Saved
```

### Queued Job Flow
```
HTTP Request (Job Dispatch)
    ↓
[Authenticated User]
    ↓
Queue::createPayloadUsing() → Captures Actor::id()
    ↓
Job Queued (with userstamps_actor_id in payload)

--- Queue Worker ---

Job Picked Up
    ↓
JobProcessing Event
    ↓
JobProcessing Listener → Actor::set($payload['userstamps_actor_id'])
    ↓
[Actor Context Restored]
    ↓
Job Executes
    ↓
    Model Operations
        ↓
    Actor::id() → returns stored actor ID
        ↓
    Model Event (creating/updating/deleting)
        ↓
    Listener sets created_by/updated_by/deleted_by
        ↓
    Model Saved
    ↓
Job Completed
    ↓
JobProcessed Event
    ↓
JobProcessed Listener → Actor::clear()
    ↓
[Actor Context Cleared]
```

## Listener Registration

All listeners are registered in `UserstampsServiceProvider`:

### Model Event Listeners
Registered in the `Userstamps` trait via `registerListeners()`:
```php
static::creating('Mattiverse\Userstamps\Listeners\Creating@handle');
static::updating('Mattiverse\Userstamps\Listeners\Updating@handle');
static::deleting('Mattiverse\Userstamps\Listeners\Deleting@handle');
static::restoring('Mattiverse\Userstamps\Listeners\Restoring@handle');
```

### Queue Event Listeners
Registered in `UserstampsServiceProvider::registerQueueSupport()`:
```php
Event::listen(
    JobProcessing::class,
    \Mattiverse\Userstamps\Listeners\Queue\JobProcessing::class
);

Event::listen(
    JobProcessed::class,
    \Mattiverse\Userstamps\Listeners\Queue\JobProcessed::class
);
```

## Benefits of Listener-Based Architecture

1. **Separation of Concerns**: Each listener has a single responsibility
2. **Testability**: Listeners can be unit tested independently
3. **Maintainability**: Easy to modify or extend specific behaviors
4. **Discoverability**: Clear file structure shows all event handlers
5. **Laravel Conventions**: Follows standard Laravel patterns
6. **Extensibility**: Users can override or extend listeners if needed

## Customization Options

Users can override queue listeners by registering their own:

```php
// In a service provider
Event::forget(\Illuminate\Queue\Events\JobProcessing::class);

Event::listen(
    \Illuminate\Queue\Events\JobProcessing::class,
    YourCustomJobProcessingListener::class
);
```

Or extend the existing listeners:

```php
namespace App\Listeners;

use Mattiverse\Userstamps\Listeners\Queue\JobProcessing as BaseJobProcessing;

class CustomJobProcessing extends BaseJobProcessing
{
    public function handle($event): void
    {
        parent::handle($event);
        
        // Your custom logic
    }
}
```
