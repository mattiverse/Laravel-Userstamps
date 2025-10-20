<?php

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Schema;
use Mattiverse\Userstamps\Actor;
use Mattiverse\Userstamps\Traits\Userstamps as UserstampsTrait;
use Orchestra\Testbench\TestCase;

class QueueUserstampsTest extends TestCase
{
    protected array $afterApplicationCreatedCallbacks = [
        'QueueUserstampsTest::handleSetup',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Clear actor state
        Actor::clear();
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.debug', 'true');
        $app['config']->set('auth.providers.users.model', QueueTestUser::class);

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('queue.default', 'sync');
        $app['config']->set('hashing', ['driver' => 'bcrypt']);
    }

    protected function getPackageProviders($app)
    {
        return ['Mattiverse\Userstamps\UserstampsServiceProvider'];
    }

    protected static function handleSetUp(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('remember_token')->nullable();
        });

        Schema::create('queue_test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });

        QueueTestUser::create(['id' => 1]);
        QueueTestUser::create(['id' => 2]);
    }

    public function test_queue_payload_includes_actor_id_when_user_is_authenticated(): void
    {
        $this->app['auth']->loginUsingId(1);

        // Dispatch a job and verify the model is created with correct userstamps
        dispatch(new CreateModelJob('Authenticated Test'));

        $model = QueueTestModel::where('name', 'Authenticated Test')->first();

        $this->assertNotNull($model);
        $this->assertEquals(1, $model->created_by);
    }

    public function test_queue_payload_has_null_actor_id_when_no_user_authenticated(): void
    {
        $this->app['auth']->logout();

        // Dispatch a job when no user is authenticated
        dispatch(new CreateModelJob('Unauthenticated Test'));

        $model = QueueTestModel::where('name', 'Unauthenticated Test')->first();

        $this->assertNotNull($model);
        $this->assertNull($model->created_by);
    }

    public function test_userstamps_are_maintained_in_queued_job(): void
    {
        $this->app['auth']->loginUsingId(1);

        // Dispatch a job that creates a model
        dispatch(new CreateModelJob('Test Model'));

        // The job runs synchronously, so we can check immediately
        $model = QueueTestModel::where('name', 'Test Model')->first();

        $this->assertNotNull($model);
        $this->assertEquals(1, $model->created_by);
        $this->assertEquals(1, $model->updated_by);
    }

    public function test_userstamps_are_maintained_in_queued_job_when_updating(): void
    {
        $this->app['auth']->loginUsingId(1);

        $model = QueueTestModel::create(['name' => 'Original']);

        $this->app['auth']->loginUsingId(2);

        // Dispatch a job that updates the model
        dispatch(new UpdateModelJob($model->id, 'Updated'));

        $model->refresh();

        $this->assertEquals('Updated', $model->name);
        $this->assertEquals(1, $model->created_by); // Should not change
        $this->assertEquals(2, $model->updated_by); // Should be user 2
    }

    public function test_userstamps_are_maintained_in_queued_job_when_soft_deleting(): void
    {
        $this->app['auth']->loginUsingId(1);

        $model = QueueTestModel::create(['name' => 'To Delete']);

        $this->app['auth']->loginUsingId(2);

        // Dispatch a job that deletes the model
        dispatch(new DeleteModelJob($model->id));

        $model = QueueTestModel::withTrashed()->find($model->id);

        $this->assertNotNull($model->deleted_at);
        $this->assertEquals(2, $model->deleted_by);
    }

    public function test_actor_is_cleared_after_job_completes(): void
    {
        $this->app['auth']->loginUsingId(1);

        dispatch(new CreateModelJob('Test'));

        // After the job completes, actor should be cleared
        // Logout to test the fallback
        $this->app['auth']->logout();

        $this->assertNull(Actor::id());
    }

    public function test_multiple_queued_jobs_maintain_separate_contexts(): void
    {
        $this->app['auth']->loginUsingId(1);
        dispatch(new CreateModelJob('Model 1'));

        $this->app['auth']->loginUsingId(2);
        dispatch(new CreateModelJob('Model 2'));

        $model1 = QueueTestModel::where('name', 'Model 1')->first();
        $model2 = QueueTestModel::where('name', 'Model 2')->first();

        $this->assertEquals(1, $model1->created_by);
        $this->assertEquals(2, $model2->created_by);
    }

    public function test_actor_can_be_manually_set_for_console_commands(): void
    {
        // Simulate a console command setting the actor
        Actor::set(99);

        $model = QueueTestModel::create(['name' => 'Console Created']);

        $this->assertEquals(99, $model->created_by);
        $this->assertEquals(99, $model->updated_by);

        Actor::clear();
    }
}

// Test Models and Jobs

class QueueTestModel extends Model
{
    use SoftDeletes, UserstampsTrait;

    protected $table = 'queue_test_models';

    protected $guarded = [];
}

class QueueTestUser extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'users';

    protected $guarded = [];
}

class TestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function handle(): void
    {
        // Empty job for payload testing
    }
}

class CreateModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public string $name) {}

    public function handle(): void
    {
        QueueTestModel::create(['name' => $this->name]);
    }
}

class UpdateModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public int $id, public string $name) {}

    public function handle(): void
    {
        $model = QueueTestModel::find($this->id);
        $model->update(['name' => $this->name]);
    }
}

class DeleteModelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public function __construct(public int $id) {}

    public function handle(): void
    {
        $model = QueueTestModel::find($this->id);
        $model->delete();
    }
}
