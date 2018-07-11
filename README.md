# Wildside/Userstamps

Provides an Eloquent trait to automatically maintain created_by and updated_by (and deleted_by when using SoftDeletes) on your models.

## Requirements

* This package requires PHP 5.6+
* It works with Laravel 5.x (and may work with earlier versions too).

## Installation

Require this package with composer

````
composer require wildside/userstamps
````

Migrate your Model's table to include a `created_by` and `updated_by` (and `deleted_by` if using `SoftDeletes`).

```php
$table -> unsignedInteger('created_by') -> nullable() -> default(null) -> after('created_at');
$table -> unsignedInteger('updated_by') -> nullable() -> default(null) -> after('updated_at');
```

Load the trait in your Model.

```php
use Wildside\Userstamps\Userstamps;

class Example extends Model {

    use Userstamps;
}
```

The following methods become available on your models to help retrieve the users creating, updating and deleting (if using SoftDeletes).

```php
$model -> creator; // the user who created the model
$model -> editor; // the user who last updated the model
$model -> destroyer; // the user who deleted the model
```

You can use the following methods to control Userstamps and Timestamps as needed.

```php
$model -> stopUserstamping();
$model -> startUserstamping();
$model -> stopTimestamping();
$model -> startTimestamping();
```
For example, if you want to manually set the `created_by` or `updated_by` properties on your model you would stop Userstamps being automatically maintained using the `stopUserstamping` method.

Userstamping will stop being automatically maintained by default if you stop the Laravel Timestamping. To override this ability, set `$syncUserWithTime = false;` on your model.

If you want to define the `created_by`, `updated_by`, or `deleted_by` column names, add the following class constants to your model(s).
```php
const CREATED_BY = 'created_by';
const UPDATED_BY = 'updated_by';
const DELETED_BY = 'deleted_by';
