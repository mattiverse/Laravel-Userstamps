# Wildside/Userstamps

Provides an Eloquent trait to automatically maintain created_by and updated_by (and deleted_by when using SoftDeletes) on your models.

## Requirements

* This package requires PHP 5.6+
* It works with Laravel 5.2+.

## Installation

Require this package with composer

````
composer require wildside/userstamps
````

Migrate your Model's table to include a `created_by` and `updated_by` (and `deleted_by` if using `SoftDeletes`).

```php
$table -> unsignedBigInteger('created_by') -> nullable() -> after('created_at');
$table -> unsignedBigInteger('updated_by') -> nullable() -> after('updated_at');
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

If you want to define the `created_by`, `updated_by`, or `deleted_by` column names, add the following class constants to your model(s).
```php
const CREATED_BY = 'created_by';
const UPDATED_BY = 'updated_by';
const DELETED_BY = 'deleted_by';
```

## License

This open-source software is licensed under the [MIT license](https://opensource.org/licenses/MIT).
