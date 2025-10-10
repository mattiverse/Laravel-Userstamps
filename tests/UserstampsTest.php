<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use Mattiverse\Userstamps\Traits\Userstamps as UserstampsTrait;
use Mattiverse\Userstamps\Userstamps;
use Orchestra\Testbench\TestCase;

class UserstampsTest extends TestCase
{
    protected array $afterApplicationCreatedCallbacks = [
        'UserstampsTest::handleSetup',
    ];

    protected function getEnvironmentSetUp($app): void
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

    protected function createFoo(): Foo
    {
        return Foo::create([
            'bar' => 'foo',
        ]);
    }

    protected function createFooWithSoftDeletes(): FooWithSoftDeletes
    {
        return FooWithSoftDeletes::create([
            'bar' => 'foo',
        ]);
    }

    protected function createFooWithCustomColumnNames(): FooWithCustomColumnNames
    {
        return FooWithCustomColumnNames::create([
            'bar' => 'foo',
        ]);
    }

    protected function createFooWithNullColumnNames(): FooWithNullColumnNames
    {
        return FooWithNullColumnNames::create([
            'bar' => 'foo',
        ]);
    }

    public function test_created_by_and_updated_by_is_set_on_new_model_when_user_is_present(): void
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFoo();

        $this->assertEquals(1, $foo->created_by);
        $this->assertEquals(1, $foo->updated_by);
    }

    public function test_created_by_is_null_on_new_model_when_user_is_not_present(): void
    {
        $foo = $this->createFoo();

        $this->assertNull($foo->created_by);
        $this->assertNull($foo->updated_by);
    }

    public function test_created_by_is_not_changed_when_model_is_updated(): void
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFoo();

        $this->app['auth']->loginUsingId(2);

        $foo->update([
            'bar' => 'bar',
        ]);

        $this->assertEquals(1, $foo->created_by);
    }

    public function test_updated_by_is_set_when_user_is_present(): void
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFoo();

        $this->app['auth']->loginUsingId(2);

        $foo->update([
            'bar' => 'bar',
        ]);

        $this->assertEquals(2, $foo->updated_by);
    }

    public function test_updated_by_is_not_changed_when_user_is_not_present(): void
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFoo();

        $this->app['auth']->logout();

        $foo->update([
            'bar' => 'bar',
        ]);

        $this->assertEquals(1, $foo->updated_by);
    }

    public function test_deleted_by_is_set_on_soft_deleting_model_when_user_is_present(): void
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFooWithSoftDeletes();

        $this->assertNull($foo->deleted_by);

        $foo->delete();

        $this->assertEquals(1, $foo->deleted_by);
    }

    public function test_deleted_by_is_not_set_on_soft_deleting_model_when_user_is_not_present(): void
    {
        $foo = $this->createFooWithSoftDeletes();

        $foo->delete();

        $this->assertNull($foo->deleted_by);
    }

    public function test_deleted_by_is_null_when_restoring_model(): void
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFooWithSoftDeletes();

        $foo->delete();
        $foo->restore();

        $this->assertNull($foo->deleted_by);
    }

    public function test_updated_by_is_not_changed_when_deleting_model(): void
    {
        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFooWithSoftDeletes();

        $this->app['auth']->loginUsingId(2);

        $foo->delete();

        $this->assertEquals(1, $foo->updated_by);
    }

    public function test_custom_column_names_are_supported(): void
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

    public function test_null_column_names_disable_userstamps(): void
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

    public function test_stop_userstamping_method_works(): void
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

    public function test_start_userstamping_method_works(): void
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

    public function test_creator_method_works(): void
    {
        $user = $this->app['auth']->loginUsingId(1);

        $foo = $this->createFoo();

        $this->assertEquals($user, $foo->creator);
    }

    public function test_editor_method_works(): void
    {
        $user = $this->app['auth']->loginUsingId(1);

        $foo = $this->createFoo();

        $this->assertEquals($user, $foo->editor);
    }

    public function test_destroyer_method_works(): void
    {
        $user = $this->app['auth']->loginUsingId(1);

        $foo = $this->createFooWithSoftDeletes();
        $foo->delete();

        $this->assertEquals($user, $foo->destroyer);
    }

    public function test_update_with_userstamps_method(): void
    {
        $this->app['auth']->loginUsingId(1);

        $this->createFoo();

        $this->app['auth']->loginUsingId(2);

        Foo::where('bar', 'foo')->updateWithUserstamps([
            'bar' => 'bar',
        ]);

        $this->assertEquals(2, Foo::first()->updated_by);
    }

    public function test_delete_with_userstamps_method(): void
    {
        $this->app['auth']->loginUsingId(1);

        $this->createFooWithSoftDeletes();

        $this->app['auth']->loginUsingId(2);

        FooWithSoftDeletes::where('bar', 'foo')->deleteWithUserstamps();

        $this->assertEquals(2, FooWithSoftDeletes::withTrashed()->first()->deleted_by);
    }

    public function test_delete_with_userstamps_method_doesnt_touch_updated_by(): void
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

    public function test_builder_method_works_with_custom_column_names(): void
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

    public function test_values_are_overridden_when_using_resolve_callback(): void
    {
        Userstamps::resolveUsing(fn() => 'bar');

        $this->app['auth']->loginUsingId(1);

        $foo = $this->createFooWithSoftDeletes();

        $this->assertEquals('bar', $foo->created_by);
        $this->assertEquals('bar', $foo->updated_by);

        $foo->delete();

        $this->assertEquals('bar', $foo->deleted_by);
    }

    public function test_it_can_add_userstamps_columns()
    {
        Schema::create('userstampable', function (Blueprint $table) {
            $table->id();
            $table->userstamps('unsignedBigInteger');
        });

        $colummns = Schema::getColumnListing('userstampable');

        $this->assertContains('created_by', $colummns);
        $this->assertContains('updated_by', $colummns);
    }

    public function test_it_can_add_userstamps_soft_delete_column()
    {
        Schema::create('userstampable', function (Blueprint $table) {
            $table->id();
            $table->userstampSoftDeletes('unsignedBigInteger');
        });

        $colummns = Schema::getColumnListing('userstampable');

        $this->assertContains('deleted_by', $colummns);
    }

    public function test_it_can_drop_userstamps_columns()
    {
        Schema::create('userstampable', function (Blueprint $table) {
            $table->id();
            $table->userstamps('unsignedBigInteger');
        });

        Schema::table('userstampable', function (Blueprint $table) {
            $table->dropUserstamps();
        });

        $colummns = Schema::getColumnListing('userstampable');

        $this->assertNotContains('created_by', $colummns);
        $this->assertNotContains('updated_by', $colummns);
    }

    public function test_it_can_drop_userstamps_soft_delete_column()
    {
        Schema::create('userstampable', function (Blueprint $table) {
            $table->id();
            $table->userstampSoftDeletes('unsignedBigInteger');
        });

        Schema::table('userstampable', function (Blueprint $table) {
            $table->dropUserstampSoftDeletes();
        });

        $colummns = Schema::getColumnListing('userstampable');

        $this->assertNotContains('deleted_by', $colummns);
    }
}

class Foo extends Model
{
    use UserstampsTrait;

    public $timestamps = false;

    protected $table = 'foos';

    protected $guarded = [];
}

class FooWithSoftDeletes extends Model
{
    use SoftDeletes, UserstampsTrait;

    protected $table = 'foos';

    protected $guarded = [];
}

class FooWithCustomColumnNames extends Model
{
    use SoftDeletes, UserstampsTrait;

    protected $table = 'foos';

    protected $guarded = [];

    const CREATED_BY = 'alt_created_by';

    const UPDATED_BY = 'alt_updated_by';

    const DELETED_BY = 'alt_deleted_by';
}

class FooWithNullColumnNames extends Model
{
    use SoftDeletes, UserstampsTrait;

    protected $table = 'foos';

    protected $guarded = [];

    const CREATED_BY = null;

    const UPDATED_BY = null;

    const DELETED_BY = null;
}

class TestUser extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'users';

    protected $guarded = [];
}
