<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Schema;
use Mattiverse\Userstamps\Traits\Userstamps as UserstampsTrait;
use Mattiverse\Userstamps\Traits\UuidManager as UuidManagerTrait;
use Mattiverse\Userstamps\Userstamps;
use Orchestra\Testbench\TestCase;

class UserstampsUuidTest extends TestCase
{
    protected array $afterApplicationCreatedCallbacks = [
        'UserstampsUuidTest::handleSetup',
    ];

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('app.debug', 'true');
        $app['config']->set('auth.providers.users.model', TestUUIDUser::class);

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
            $table->uuid('id')->primary();
            $table->string('remember_token')->nullable();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->uuid('deleted_by')->nullable();
            $table->uuid('alt_created_by')->nullable();
            $table->uuid('alt_updated_by')->nullable();
            $table->uuid('alt_deleted_by')->nullable();
        });
    }

    protected function createPost(): Post
    {
        return Post::create([
            'id' => UuidManagerTrait::generateUUID(),
            'title' => 'news',
        ]);
    }

    protected function createPostWithSoftDeletes(): PostWithSoftDeletes
    {
        return PostWithSoftDeletes::create([
            'id' => UuidManagerTrait::generateUUID(),
            'title' => 'sports',
        ]);
    }

    protected function createPostWithCustomColumnNames(): PostWithCustomColumnNames
    {
        return PostWithCustomColumnNames::create([
            'id' => UuidManagerTrait::generateUUID(),
            'title' => 'politics',
        ]);
    }

    protected function createPostWithNullColumnNames(): PostWithNullColumnNames
    {
        return PostWithNullColumnNames::create([
            'id' => UuidManagerTrait::generateUUID(),
            'title' => 'Programming',
        ]);
    }

    protected function createTestUuidUser(): TestUUIDUser
    {
        return TestUUIDUser::create([
            'id' => UuidManagerTrait::generateUUID(),
        ]);
    }

    public function test_it_can_add_userstampsuuid_columns(): void
    {
        Schema::create('userstampable', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->userstampsUuid();
        });

        $colummns = Schema::getColumnListing('userstampable');

        $this->assertContains('created_by', $colummns);
        $this->assertContains('updated_by', $colummns);
    }

    public function test_it_can_add_userstampsuuid_soft_delete_column(): void
    {
        Schema::create('userstampable', function (Blueprint $table) {
            $table->id();
            $table->userstampsUuidSoftDeletes();
        });

        $colummns = Schema::getColumnListing('userstampable');

        $this->assertContains('deleted_by', $colummns);
    }

    public function test_it_can_add_both_userstampsuuid_columns_and_userstampsuuid_soft_delete_column(): void
    {
        Schema::create('userstampable', function (Blueprint $table) {
            $table->id();
            $table->userstampsUuid();
            $table->userstampsUuidSoftDeletes();
        });

        $colummns = Schema::getColumnListing('userstampable');

        $this->assertContains('deleted_by', $colummns);
        $this->assertContains('created_by', $colummns);
        $this->assertContains('updated_by', $colummns);
    }

    public function test_it_can_drop_userstampsuuid_columns(): void
    {
        Schema::create('userstampable', function (Blueprint $table) {
            $table->id();
            $table->userstampsUuid();
        });

        Schema::table('userstampable', function (Blueprint $table) {
            $table->dropUserstampUuid();
        });

        $colummns = Schema::getColumnListing('userstampable');

        $this->assertNotContains('created_by', $colummns);
        $this->assertNotContains('updated_by', $colummns);
    }

    public function test_it_can_drop_userstampsuuid_soft_delete_column(): void
    {
        Schema::create('userstampable', function (Blueprint $table) {
            $table->id();
            $table->userstampsUuidSoftDeletes();
        });

        Schema::table('userstampable', function (Blueprint $table) {
            $table->dropUserstampUuidSoftDeletes();
        });

        $colummns = Schema::getColumnListing('userstampable');

        $this->assertNotContains('deleted_by', $colummns);
    }

    public function test_created_by_and_updated_by_columns_are_set_on_new_model_when_user_is_present(): void
    {
        $createdUser = $this->createTestUuidUser();

        $this->app['auth']->login($createdUser);

        UuidManagerTrait::resolveAuthenticatedUserId($createdUser);

        $post = $this->createPost();

        $this->assertEquals($createdUser->id, $post->created_by);
        $this->assertEquals($createdUser->id, $post->updated_by);
    }

    public function test_created_by_is_null_on_new_model_when_user_is_not_present(): void
    {
        Userstamps::resolveUsing(function () {
            return null;
        });
        $post = $this->createPost();

        $this->assertNull($post->created_by);
        $this->assertNull($post->updated_by);
    }

    public function test_created_by_is_not_changed_when_model_is_updated(): void
    {
        $createdUser = $this->createTestUuidUser();

        $this->app['auth']->login($createdUser);

        UuidManagerTrait::resolveAuthenticatedUserId($createdUser);

        $post = $this->createPost();

        $anotherUser = $this->createTestUuidUser();

        $this->app['auth']->login($anotherUser);

        UuidManagerTrait::resolveAuthenticatedUserId($anotherUser);

        $post->update([
            'title' => 'changed title',
        ]);

        $this->assertEquals($createdUser->id, $post->created_by);
    }

    public function test_updated_by_is_set_when_user_exist(): void
    {
        $createdUser = $this->createTestUuidUser();

        $this->app['auth']->login($createdUser);

        UuidManagerTrait::resolveAuthenticatedUserId($createdUser);

        $post = $this->createPost();

        $anotherUser = $this->createTestUuidUser();

        $this->app['auth']->login($anotherUser);

        UuidManagerTrait::resolveAuthenticatedUserId($anotherUser);

        $post->update([
            'title' => 'changed title',
        ]);

        $this->assertEquals($anotherUser->id, $post->updated_by);
    }

    public function test_updated_by_is_not_changed_when_user_is_not_present(): void
    {
        $createdUser = $this->createTestUuidUser();

        $this->app['auth']->login($createdUser);

        UuidManagerTrait::resolveAuthenticatedUserId($createdUser);

        $post = $this->createPost();

        $this->app['auth']->logout();

        $post->update([
            'title' => 'changed title',
        ]);

        $this->assertEquals($createdUser->id, $post->updated_by);
    }

    public function test_deleted_by_is_set_on_soft_deleting_model_when_user_is_present(): void
    {
        $createdUser = $this->createTestUuidUser();

        $this->app['auth']->login($createdUser);

        UuidManagerTrait::resolveAuthenticatedUserId($createdUser);

        $post = $this->createPostWithSoftDeletes();

        $this->assertNull($post->deleted_by);

        $post->delete();

        $this->assertEquals($createdUser->id, $post->deleted_by);
    }

    public function test_deleted_by_is_not_set_on_soft_deleting_model_when_user_is_not_present(): void
    {
        Userstamps::resolveUsing(function () {
            return null;
        });
        $post = $this->createPostWithSoftDeletes();

        $post->delete();

        $this->assertNull($post->deleted_by);
    }

    public function test_deleted_by_is_null_when_restoring_model(): void
    {
        $createdUser = $this->createTestUuidUser();

        $this->app['auth']->login($createdUser);

        UuidManagerTrait::resolveAuthenticatedUserId($createdUser);

        $post = $this->createPostWithSoftDeletes();

        $post->delete();
        $post->restore();

        $this->assertNull($post->deleted_by);
    }

    public function test_created_by_and_updated_by_are_not_changed_when_deleting_model(): void
    {
        $createdUser = $this->createTestUuidUser();

        $this->app['auth']->login($createdUser);

        UuidManagerTrait::resolveAuthenticatedUserId($createdUser);

        $post = $this->createPostWithSoftDeletes();

        $anotherUser = $this->createTestUuidUser();

        $this->app['auth']->login($anotherUser);

        UuidManagerTrait::resolveAuthenticatedUserId($anotherUser);

        $post->delete();

        $this->assertEquals($createdUser->id, $post->created_by);
        $this->assertEquals($createdUser->id, $post->updated_by);
    }

    public function test_custom_column_names_are_supported(): void
    {
        $createdUser = $this->createTestUuidUser();

        $this->app['auth']->login($createdUser);

        UuidManagerTrait::resolveAuthenticatedUserId($createdUser);

        $post = $this->createPostWithCustomColumnNames();

        $this->assertEquals($createdUser->id, $post->alt_created_by);
        $this->assertEquals($createdUser->id, $post->alt_updated_by);
        $this->assertNull($post->created_by);
        $this->assertNull($post->updated_by);

        $anotherUser = $this->createTestUuidUser();

        $this->app['auth']->login($anotherUser);

        UuidManagerTrait::resolveAuthenticatedUserId($anotherUser);

        $post->update([
            'title' => 'title changed',
        ]);

        $this->assertEquals($anotherUser->id, $post->alt_updated_by);
        $this->assertNull($post->updated_by);

        $post->delete();

        $this->assertEquals($anotherUser->id, $post->alt_deleted_by);
        $this->assertNull($post->deleted_by);

        $post->restore();
        $this->assertNull($post->alt_deleted_by);
    }

    public function test_null_column_names_disable_userstamps(): void
    {
        $createdUser = $this->createTestUuidUser();

        $this->app['auth']->login($createdUser);

        UuidManagerTrait::resolveAuthenticatedUserId($createdUser);

        $post = $this->createPostWithNullColumnNames();

        $this->assertNull($post->created_by);
        $this->assertNull($post->updated_by);

        $anotherUser = $this->createTestUuidUser();

        $this->app['auth']->login($anotherUser);

        UuidManagerTrait::resolveAuthenticatedUserId($anotherUser);

        $post->update([
            'title' => 'title changed',
        ]);

        $this->assertNull($post->updated_by);

        $post->delete();

        $this->assertNull($post->deleted_by);

        $post->restore();
        $this->assertNull($post->deleted_by);
    }

    public function test_stop_userstamping_method_works(): void
    {
        $createdUser = $this->createTestUuidUser();

        $this->app['auth']->login($createdUser);

        UuidManagerTrait::resolveAuthenticatedUserId($createdUser);

        $post = $this->createPostWithSoftDeletes();
        $post->stopUserstamping();

        $anotherUser = $this->createTestUuidUser();

        $this->app['auth']->login($anotherUser);

        UuidManagerTrait::resolveAuthenticatedUserId($anotherUser);

        $post->update([
            'title' => 'title changed',
        ]);

        $this->assertEquals($createdUser->id, $post->updated_by);

        $post->delete();

        $this->assertNull($post->deleted_by);
    }

    public function test_start_userstamping_method_works(): void
    {
        $createdUser = $this->createTestUuidUser();

        $this->app['auth']->login($createdUser);

        UuidManagerTrait::resolveAuthenticatedUserId($createdUser);

        $post = $this->createPostWithSoftDeletes();
        $post->stopUserstamping();
        $post->startUserstamping();

        $anotherUser = $this->createTestUuidUser();

        $this->app['auth']->login($anotherUser);

        UuidManagerTrait::resolveAuthenticatedUserId($anotherUser);

        $post->update([
            'title' => 'title changed',
        ]);

        $this->assertEquals($anotherUser->id, $post->updated_by);

        $post->delete();

        $this->assertEquals($anotherUser->id, $post->deleted_by);
    }

    public function test_creator_method_works(): void
    {
        $createdUser = $this->createTestUuidUser();

        $authUser = $this->app['auth']->login($createdUser);

        $post = $this->createPost();

        $this->assertEquals($authUser, $post->creator);
    }

    public function test_editor_method_works(): void
    {
        $createdUser = $this->createTestUuidUser();

        $user = $this->app['auth']->login($createdUser);

        $post = $this->createPost();

        $this->assertEquals($user, $post->editor);
    }

    public function test_destroyer_method_works(): void
    {
        $createdUser = $this->createTestUuidUser();

        $user = $this->app['auth']->login($createdUser);

        $post = $this->createPostWithSoftDeletes();
        $post->delete();

        $this->assertEquals($user, $post->destroyer);
    }

    public function test_update_with_userstamps_method(): void
    {
        $createdUser = $this->createTestUuidUser();

        $this->app['auth']->login($createdUser);

        UuidManagerTrait::resolveAuthenticatedUserId($createdUser);

        $this->createPost();

        $anotherUser = $this->createTestUuidUser();

        $this->app['auth']->login($anotherUser);

        UuidManagerTrait::resolveAuthenticatedUserId($anotherUser);

        Post::where('title', 'politics')->updateWithUserstamps([
            'title' => 'Laravel',
        ]);

        $this->assertEquals($createdUser->id, Post::first()->updated_by);
    }

    public function test_delete_with_userstamps_method(): void
    {
        $createdUser = $this->createTestUuidUser();

        $this->app['auth']->login($createdUser);

        UuidManagerTrait::resolveAuthenticatedUserId($createdUser);

        $this->createPostWithSoftDeletes();

        $anotherUser = $this->createTestUuidUser();

        $this->app['auth']->login($anotherUser);

        UuidManagerTrait::resolveAuthenticatedUserId($anotherUser);

        PostWithSoftDeletes::where('title', 'sports')->deleteWithUserstamps();

        $deletedPost = PostWithSoftDeletes::withTrashed()->first();

        $this->assertEquals($anotherUser->id, $deletedPost->deleted_by);
    }

    public function test_delete_with_userstamps_method_doesnt_touch_updated_by(): void
    {
        $createdUser = $this->createTestUuidUser();

        $this->app['auth']->login($createdUser);

        UuidManagerTrait::resolveAuthenticatedUserId($createdUser);

        $post = $this->createPostWithSoftDeletes();

        $anotherUser = $this->createTestUuidUser();

        $this->app['auth']->login($anotherUser);

        UuidManagerTrait::resolveAuthenticatedUserId($anotherUser);

        PostWithSoftDeletes::where('title', 'sports')->deleteWithUserstamps();

        $post = PostWithSoftDeletes::withTrashed()->first();

        $this->assertEquals($createdUser->id, $post->updated_by);
        $this->assertEquals($anotherUser->id, $post->deleted_by);
    }

    public function test_builder_method_works_with_custom_column_names(): void
    {
        $createdUser = $this->createTestUuidUser();

        $this->app['auth']->login($createdUser);

        UuidManagerTrait::resolveAuthenticatedUserId($createdUser);

        $this->createPostWithCustomColumnNames();

        $anotherUser = $this->createTestUuidUser();

        $this->app['auth']->login($anotherUser);

        UuidManagerTrait::resolveAuthenticatedUserId($anotherUser);

        PostWithCustomColumnNames::where('title', 'politics')->updateWithUserstamps([
            'title' => 'politics changed',
        ]);

        PostWithCustomColumnNames::where('title', 'politics changed')->deleteWithUserstamps();

        $post = PostWithCustomColumnNames::withTrashed()->where('title', 'politics changed')->first();

        $this->assertNull($post->updated_by);
        $this->assertNull($post->deleted_by);
        $this->assertEquals($anotherUser->id, $post->alt_updated_by);
        $this->assertEquals($anotherUser->id, $post->alt_deleted_by);
    }

    public function test_values_are_overridden_when_using_resolve_callback(): void
    {
        Userstamps::resolveUsing(fn () => 'bar');

        $createdUser = $this->createTestUuidUser();

        $this->app['auth']->login($createdUser);

        $post = $this->createPostWithSoftDeletes();

        $this->assertEquals('bar', $post->created_by);
        $this->assertEquals('bar', $post->updated_by);

        $post->delete();

        $this->assertEquals('bar', $post->deleted_by);
    }
}

class Post extends Model
{
    use UserstampsTrait;

    public $timestamps = false;

    protected $table = 'posts';

    protected $guarded = [];

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';
}

class PostWithSoftDeletes extends Model
{
    use SoftDeletes, UserstampsTrait;

    protected $table = 'posts';

    protected $guarded = [];

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';
}

class PostWithCustomColumnNames extends Model
{
    use SoftDeletes, UserstampsTrait;

    protected $table = 'posts';

    protected $guarded = [];

    const CREATED_BY = 'alt_created_by';

    const UPDATED_BY = 'alt_updated_by';

    const DELETED_BY = 'alt_deleted_by';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';
}

class PostWithNullColumnNames extends Model
{
    use SoftDeletes, UserstampsTrait;

    protected $table = 'posts';

    protected $guarded = [];

    const CREATED_BY = null;

    const UPDATED_BY = null;

    const DELETED_BY = null;

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';
}

class TestUUIDUser extends Authenticatable
{
    public $timestamps = false;

    protected $table = 'users';

    protected $guarded = [];

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $keyType = 'string';
}
