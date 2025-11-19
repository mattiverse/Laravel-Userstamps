<p align="center">
    <img src="./logo.svg" width="300">
</p>

<div align="center">

[![Total Downloads](https://poser.pugx.org/wildside/userstamps/downloads)](https://packagist.org/packages/wildside/userstamps)
[![Latest Stable Version](https://poser.pugx.org/wildside/userstamps/v)](https://packagist.org/packages/wildside/userstamps)
[![License](https://poser.pugx.org/wildside/userstamps/license)](https://packagist.org/packages/wildside/userstamps)

</div>

## About Laravel Userstamps

Laravel Userstamps provides an Eloquent trait which automatically maintains `created_by` and `updated_by` columns on your model, populated by the currently authenticated user in your application.

When using the Laravel `SoftDeletes` trait, a `deleted_by` column is also handled by this package.

## Installing

This package requires Laravel 9 or later running on PHP 8.2 or higher.

This package can be installed using composer:

```
composer require wildside/userstamps
```

## Usage

Your model will need to include a `created_by` and `updated_by` column, defaulting to `null`.

If using the Laravel `SoftDeletes` trait, it will also need a `deleted_by` column.

The column type should match the type of the ID column in your user's table.

Userstamp columns can be created using:

```php
$table->userstamps();
$table->userstampSoftDeletes();
```

Equivalent methods are also available when working with UUIDs.

```php
$table->userstampsUuid();
$table->userstampsUuidSoftDeletes();
```

You can now load the trait within your model, and userstamps will automatically be maintained:

```php
use Mattiverse\Userstamps\Traits\Userstamps;

class Foo extends Model {

    use Userstamps;
}
```

Optionally, should you wish to override the names of the `created_by`, `updated_by` or `deleted_by` columns, you can do so by setting the appropriate class constants on your model. Ensure you match these column names in your migration.

```php
use Mattiverse\Userstamps\Traits\Userstamps;

class Foo extends Model {

    use Userstamps;

    const CREATED_BY = 'alt_created_by';
    const UPDATED_BY = 'alt_updated_by';
    const DELETED_BY = 'alt_deleted_by';
}
```

When using this trait, helper relationships are available to let you retrieve the user who created, updated and deleted (when using the Laravel `SoftDeletes` trait) your model.

```php
$model->creator; // the user who created the model
$model->editor; // the user who last updated the model
$model->destroyer; // the user who deleted the model
```

Methods are also available to temporarily stop the automatic maintaining of userstamps on your models:

```php
$model->stopUserstamping(); // stops userstamps being maintained on the model
$model->startUserstamping(); // resumes userstamps being maintained on the model
```

## Resolving Users

By default users will be resolved using the Laravel `Auth::id()` method, to return the ID of the currently authenticated user.

More advanced use-cases are supported with a custom resolution strategy.

In this example, a custom resolution method is called to retrieve the ID.

```php
use Mattiverse\Userstamps\Userstamps;

Userstamps::resolveUsing(
    fn () => auth()->user()->customUserIdResolutionMethod()
);
```

The `Userstamps::resolveUsing` method is likely best suited to the `boot` method of `AppServiceProvider`.

## Workarounds

This package works by hooking into Eloquent's model event listeners, and is subject to the same limitations of all such listeners.

When you make changes to models that bypass Eloquent, the event listeners won't be fired and userstamps will not be updated.

Commonly this will happen if bulk updating or deleting models, or their relations.

In this example, model relations are updated via Eloquent and userstamps **will** be maintained:

```php
$model->foos->each(function ($item) {
    $item->bar = 'x';
    $item->save();
});
```

However in this example, model relations are bulk updated and bypass Eloquent. Userstamps **will not** be maintained:

```php
$model->foos()->update([
    'bar' => 'x',
]);
```

As a workaroud to this issue two helper methods are available - `updateWithUserstamps` and `deleteWithUserstamps`. Their behaviour is identical to `update` and `delete`, but they ensure the `updated_by` and `deleted_by` properties are maintained on the model.

You generally won't have to use these methods, unless making bulk updates that bypass Eloquent events.

In this example, models are bulk updated and userstamps **will not** be maintained:

```php
$model->where('name', 'foo')->update([
    'name' => 'bar',
]);
```

However in this example, models are bulk updated using the helper method and userstamps **will** be maintained:

```php
$model->where('name', 'foo')->updateWithUserstamps([
    'name' => 'bar',
]);
```

## License

This open-source software is licensed under the [MIT license](https://opensource.org/licenses/MIT).
