<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;
use Wildside\Userstamps\Userstamps;

class UserstampsTest extends TestCase
{
    /**
     * The callbacks that should be run after the application is created.
     *
     * @var array
     */
    protected array $afterApplicationCreatedCallbacks = [
        'UserstampsTest::handleSetup',
    ];

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');
        $app['config']->set('auth.providers.users.model', TestUser::class);

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('hashing', ['driver' => 'bcrypt']);
    }

    protected static function handleSetUp()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('remember_token')->nullable();
        });

        Schema::create('foos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('bar');
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->unsignedBigInteger('alt_created_by')->nullable();
            $table->unsignedBigInteger('alt_updated_by')->nullable();
            $table->unsignedBigInteger('alt_deleted_by')->nullable();
        });

        TestUser::create([
            'id' => 1,
        ]);

        TestUser::create([
            'id' => 2,
        ]);
    }

    protected function createFoo()
    {
        return Foo::create([
            'bar' => 'foo',
        ]);
    }

    protected function createFooWithSoftDeletes()
    {
        return FooWithSoftDeletes::create([
            'bar' => 'foo',
        ]);
    }

    protected function createFooWithCustomColumnNames()
    {
        return FooWithCustomColumnNames::create([
            'bar' => 'foo',
        ]);
    }

    protected function createFooWithNullColumnNames()
    {
        return FooWithNullColumnNames::create([
            'bar' => 'foo',
        ]);
    }

    public function testCreatedByAndUpdatedByIsSetOnNewModelWhenUserIsPresent()
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFoo();

        $this->assertEquals(1, $foo->created_by);
        $this->assertEquals(1, $foo->updated_by);
    }

    public function testCreatedByIsNullOnNewModelWhenUserIsNotPresent()
    {
        $foo = $this->createFoo();

        $this->assertNull($foo->created_by);
        $this->assertNull($foo->updated_by);
    }

    public function testCreatedByIsNotChangedWhenModelIsUpdated()
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFoo();

        $this->app['auth']->loginUsingId(2);

        $foo->update([
            'bar' => 'bar',
        ]);

        $this->assertEquals(1, $foo->created_by);
    }

    public function testUpdatedByIsSetWhenUserIsPresent()
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFoo();

        $this->app['auth']->loginUsingId(2);

        $foo->update([
            'bar' => 'bar',
        ]);

        $this->assertEquals(2, $foo->updated_by);
    }

    public function testUpdatedByIsNotChangedWhenUserIsNotPresent()
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFoo();

        $this->app['auth']->logout();

        $foo->update([
            'bar' => 'bar',
        ]);

        $this->assertEquals(1, $foo->updated_by);
    }

    public function testDeletedByIsSetOnSoftDeletingModelWhenUserIsPresent()
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFooWithSoftDeletes();

        $this->assertNull($foo->deleted_by);

        $foo->delete();

        $this->assertEquals(1, $foo->deleted_by);
    }

    public function testDeletedByIsNotSetOnSoftDeletingModelWhenUserIsNotPresent()
    {
        $foo = $this->createFooWithSoftDeletes();

        $foo->delete();

        $this->assertNull($foo->deleted_by);
    }

    public function testDeletedByIsNullWhenRestoringModel()
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFooWithSoftDeletes();

        $foo->delete();
        $foo->restore();

        $this->assertNull($foo->deleted_by);
    }

    public function testUpdatedByIsNotChangedWhenDeletingModel()
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFooWithSoftDeletes();

        $this->app['auth']->loginUsingId(2);

        $foo->delete();

        $this->assertEquals(1, $foo->updated_by);
    }

    public function testCustomColumnNamesAreSupported()
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFooWithCustomColumnNames();

        $this->assertEquals(1, $foo->alt_created_by);
        $this->assertEquals(1, $foo->alt_updated_by);
        $this->assertNull($foo->created_by);
        $this->assertNull($foo->updated_by);

        $this->app['auth']->loginUsingId(2);

        $foo->update([
            'bar' => 'bar',
        ]);

        $this->assertEquals(2, $foo->alt_updated_by);
        $this->assertNull($foo->updated_by);

        $foo->delete();

        $this->assertEquals(2, $foo->alt_deleted_by);
        $this->assertNull($foo->deleted_by);

        $foo->restore();
        $this->assertNull($foo->alt_deleted_by);
    }

    public function testNullColumnNamesDisableUserstamps()
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFooWithNullColumnNames();

        $this->assertNull($foo->created_by);
        $this->assertNull($foo->updated_by);

        $this->app['auth']->loginUsingId(2);

        $foo->update([
            'bar' => 'bar',
        ]);

        $this->assertNull($foo->updated_by);

        $foo->delete();

        $this->assertNull($foo->deleted_by);

        $foo->restore();
        $this->assertNull($foo->deleted_by);
    }

    public function testStopUserstampingMethodWorks()
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFooWithSoftDeletes();
        $foo->stopUserstamping();

        $this->app['auth']->loginUsingId(2);

        $foo->update([
            'bar' => 'bar',
        ]);

        $this->assertEquals(1, $foo->updated_by);

        $foo->delete();

        $this->assertNull($foo->deleted_by);
    }

    public function testStartUserstampingMethodWorks()
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFooWithSoftDeletes();
        $foo->stopUserstamping();
        $foo->startUserstamping();

        $this->app['auth']->loginUsingId(2);

        $foo->update([
            'bar' => 'bar',
        ]);

        $this->assertEquals(2, $foo->updated_by);

        $foo->delete();

        $this->assertEquals(2, $foo->deleted_by);
    }

    public function testCreatorMethodWorks()
    {
        $user = $this->app['auth']->loginUsingId(1);

        $foo = $this->createFoo();

        $this->assertEquals($user, $foo->creator);
    }

    public function testEditorMethodWorks()
    {
        $user = $this->app['auth']->loginUsingId(1);

        $foo = $this->createFoo();

        $this->assertEquals($user, $foo->editor);
    }

    public function testDestroyerMethodWorks()
    {
        $user = $this->app['auth']->loginUsingId(1);

        $foo = $this->createFooWithSoftDeletes();
        $foo->delete();

        $this->assertEquals($user, $foo->destroyer);
    }

    public function testUpdateWithUserstampsMethod()
    {
        $this->app['auth']->loginUsingId(1);

        $this->createFoo();

        $this->app['auth']->loginUsingId(2);

        Foo::where('bar', 'foo')->updateWithUserstamps([
            'bar' => 'bar',
        ]);

        $this->assertEquals(2, Foo::first()->updated_by);
    }

    public function testDeleteWithUserstampsMethod()
    {
        $this->app['auth']->loginUsingId(1);

        $this->createFooWithSoftDeletes();

        $this->app['auth']->loginUsingId(2);

        FooWithSoftDeletes::where('bar', 'foo')->deleteWithUserstamps();

        $this->assertEquals(2, FooWithSoftDeletes::withTrashed()->first()->deleted_by);
    }

    public function testDeleteWithUserstampsMethodDoesntTouchUpdatedBy()
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFooWithSoftDeletes();
        $updatedAt = $foo->updated_at;

        $this->app['auth']->loginUsingId(2);

        FooWithSoftDeletes::where('bar', 'foo')->deleteWithUserstamps();

        $foo = FooWithSoftDeletes::withTrashed()->first();

        $this->assertEquals(1, $foo->updated_by);
        $this->assertEquals(2, $foo->deleted_by);
    }

    public function testBuilderMethodWorksWithCustomColumnNames()
    {
        $this->app['auth']->loginUsingId(1);

        $this->createFooWithCustomColumnNames();

        $this->app['auth']->loginUsingId(2);

        FooWithCustomColumnNames::where('bar', 'foo')->updateWithUserstamps([
            'bar' => 'bar',
        ]);

        FooWithCustomColumnNames::where('bar', 'bar')->deleteWithUserstamps();

        $foo = FooWithCustomColumnNames::withTrashed()->where('bar', 'bar')->first();

        $this->assertNull($foo->updated_by);
        $this->assertNull($foo->deleted_by);
        $this->assertEquals(2, $foo->alt_updated_by);
        $this->assertEquals(2, $foo->alt_deleted_by);
    }
}

class Foo extends Model
{
    use Userstamps;

    public $table = 'foos';
    public $timestamps = false;
    protected $guarded = [];
}

class FooWithSoftDeletes extends Model
{
    use SoftDeletes, Userstamps;

    public $table = 'foos';
    protected $guarded = [];
}

class FooWithCustomColumnNames extends Model
{
    use SoftDeletes, Userstamps;

    public $table = 'foos';
    protected $guarded = [];

    const CREATED_BY = 'alt_created_by';
    const UPDATED_BY = 'alt_updated_by';
    const DELETED_BY = 'alt_deleted_by';
}

class FooWithNullColumnNames extends Model
{
    use SoftDeletes, Userstamps;

    public $table = 'foos';
    protected $guarded = [];

    const CREATED_BY = null;
    const UPDATED_BY = null;
    const DELETED_BY = null;
}

class TestUser extends Authenticatable
{
    public $table = 'users';
    public $timestamps = false;
    protected $guarded = [];
}
