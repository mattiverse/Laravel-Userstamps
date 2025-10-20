<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;
use Mattiverse\Userstamps\Actor;
use Mattiverse\Userstamps\Traits\Userstamps;
use Orchestra\Testbench\TestCase;

class QueueUserstampsTest extends TestCase
{
    protected array $afterApplicationCreatedCallbacks = [
        'QueueUserstampsTest::handleSetup',
    ];

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.debug', 'true');
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function getPackageProviders($app)
    {
        return ['Mattiverse\Userstamps\UserstampsServiceProvider'];
    }

    protected static function handleSetup(): void
    {
        Schema::create('queue_test_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

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

    public function test_model_created_in_queue_job_has_correct_userstamps(): void
    {
        Actor::set(42);

        $job = new CreateModelJob('Test Model');
        $job->handle();

        Actor::clear();

        $model = QueueTestModel::first();

        $this->assertNotNull($model);
        $this->assertEquals(42, $model->created_by);
        $this->assertEquals(42, $model->updated_by);
    }

    public function test_model_updated_in_queue_job_has_correct_userstamps(): void
    {
        Actor::set(10);
        $model = QueueTestModel::create(['name' => 'Original']);
        Actor::clear();

        $this->assertEquals(10, $model->created_by);
        $this->assertEquals(10, $model->updated_by);

        Actor::set(20);
        $job = new UpdateModelJob($model->id, 'Updated');
        $job->handle();
        Actor::clear();

        $model->refresh();

        $this->assertEquals(10, $model->created_by);
        $this->assertEquals(20, $model->updated_by);
        $this->assertEquals('Updated', $model->name);
    }

    public function test_model_soft_deleted_in_queue_job_has_correct_userstamps(): void
    {
        Actor::set(10);
        $model = QueueTestModel::create(['name' => 'To Delete']);
        Actor::clear();

        Actor::set(30);
        $job = new DeleteModelJob($model->id);
        $job->handle();
        Actor::clear();

        $model = QueueTestModel::withTrashed()->find($model->id);

        $this->assertNotNull($model);
        $this->assertTrue($model->trashed());
        $this->assertEquals(30, $model->deleted_by);
    }

    public function test_actor_is_cleared_after_job_processing(): void
    {
        Actor::set(42);

        $this->assertEquals(42, Actor::id());

        Actor::clear();

        $this->assertNull(Actor::id());
    }

    public function test_actor_isolation_between_multiple_jobs(): void
    {
        Actor::set(10);
        $job1 = new CreateModelJob('Model 1');
        $job1->handle();
        Actor::clear();

        Actor::set(20);
        $job2 = new CreateModelJob('Model 2');
        $job2->handle();
        Actor::clear();

        $models = QueueTestModel::all();

        $this->assertCount(2, $models);
        $this->assertEquals(10, $models[0]->created_by);
        $this->assertEquals(20, $models[1]->created_by);
    }

    public function test_manual_actor_set_works_in_non_queue_context(): void
    {
        Actor::set(99);

        $model = QueueTestModel::create(['name' => 'Manual']);

        $this->assertEquals(99, $model->created_by);
        $this->assertEquals(99, $model->updated_by);

        Actor::clear();
    }
}

class QueueTestModel extends Model
{
    use SoftDeletes, Userstamps;

    protected $table = 'queue_test_models';

    protected $guarded = [];
}

class CreateModelJob
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public string $name) {}

    public function handle(): void
    {
        QueueTestModel::create(['name' => $this->name]);
    }
}

class UpdateModelJob
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public int $id, public string $name) {}

    public function handle(): void
    {
        $model = QueueTestModel::find($this->id);
        $model->update(['name' => $this->name]);
    }
}

class DeleteModelJob
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(public int $id) {}

    public function handle(): void
    {
        $model = QueueTestModel::find($this->id);
        $model->delete();
    }
}
