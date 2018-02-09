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

If you want to manually set the `created_by` or `updated_by` properties on your model you can stop Userstamps being automatically maintained using the `stopUserstamping` method.

If you want to use custom `created_by`, `updated_by`, or `deleted_by` column names similer to Laravel's timestamp `created_at`, `updated_at`, and `deleted_at` constants, simply add the following to whichever class you are using userstamps in.

```php
const CREATED_BY = ''; // your custom created_by column name
const UPDATED_BY = ''; // your custom updated_by column name
const DELETED_BY = ''; // your custom deleted_by column name
```