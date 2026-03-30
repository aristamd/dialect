# Dialect

[![CI](https://github.com/aristamd/dialect/actions/workflows/ci.yml/badge.svg)](https://github.com/aristamd/dialect/actions/workflows/ci.yml)

Dialect provides JSON datatype support for the [Eloquent ORM](http://laravel.com/docs/eloquent). It has been demonstrated to work with PostgreSQL and MySQL. There are lots of opportunities to enhance and improve — if you're interested in contributing please submit pull requests.

## Requirements

| Dialect | Laravel | PHP    |
|---------|---------|--------|
| 2.x     | ^13.0   | ^8.2   |
| 1.x     | 4.x – 9.x | >=5.4 |

## Installation

Require this package via Composer:

```bash
composer require aristamd/dialect
```

Or add it manually to your `composer.json`:

```json
"aristamd/dialect": "^2.0"
```

...then run `composer update`.

## Usage
### The Basics

The feature is exposed through a trait which allows you to define attributes on the model that use the JSON column type. When a model is hydrated it will parse the JSON document and set up getters and setters for each top-level attribute, making it easy to interact with the underlying data. For example, we could create a Photos model like this:

```php
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use Eloquent\Dialect\Json;
    protected $jsonColumns = ['json_data'];
}
```
And then this:
```php
$attr = json_decode($photo->json_data);
$attr->key = $value;
$photo->json_data = json_encode($attr);
```
becomes this:
```php
$photo->key = value;
```
Also when calling the toArray() method the attributes are moved to the top level and the 'json_attributes' column is hidden. This essentially hides away the fact that you're using the json datatype and makes it look like we're working with attributes directly.

### Relations
You can also establish relationships on a model like this (only supported in PostgreSQL):
```php
public function user()
{
    return $this->hasOne( 'User', 'id', "json_data->>'user_id'" );
}
```

### Structure Hinting
Sometimes you may have an empty or partially populated record in which case the trait cannot automatically detect and create getters/setters, etc... When getting or setting an attribute not previously set in the JSON document you'll get an exception. You have two choices to deal with this. You can hint at the full structure as in the example below:
```php
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use Eloquent\Dialect\Json;
    protected $jsonColumns = ['json_data'];

    public function __construct()
    {
        parent::__construct();
        $this->hintJsonStructure( 'json_data', '{"foo":null}' );
    }
}
```
Once you create a hint you will be able to make calls to get and set json attributes e.g. `$photo->foo = 'bar';` regardless of whether or not they are already defined in the underlying db record. Alternatly if you prefer not to hint structures then you may call `setJsonAttribute()`. For example if you defined a json column called "json_data" and wanted to set an attribute called 'fizz' so you could call:
```php
$photo->setJsonAttribute( 'json_data', 'fizz', 'buzz' );
```
### Showing/Hiding Attributes
One of the aims of the project is to make json attributes "first class" citizens of the model. This means by default we add the attributes to the models appends array so that when you call `$model->toArray()` or `$model->toJson()` the attribute shows up as a part of the structure like a normal attribute. By default we also hide away the json column holding the underlying data. Both of these settings can be changed using the `showJsonColumns()` and `showJsonAttributes()` as shown below:
```php
use Illuminate\Database\Eloquent\Model;

class Photo extends Model
{
    use Eloquent\Dialect\Json;
    protected $jsonColumns = ['json_data'];

    public function __construct()
    {
        parent::__construct();
        $this->showJsonColumns(true);
        $this->showJsonAttributes(false);
    }
}
```

## Upgrading from 1.x to 2.x

2.x is a breaking release that drops legacy framework support in favour of Laravel 13 and PHP 8.2+.

### Required changes

| Before (1.x) | After (2.x) |
|---|---|
| `class Photo extends Eloquent` | `class Photo extends Illuminate\Database\Eloquent\Model` |
| `illuminate/support: 4.*–9.*` | `illuminate/support: ^13.0` |
| `php: >=5.4` | `php: ^8.2` |
| PHPUnit 4.x in tests | PHPUnit 11.x |

### Behavioral changes
- `setAttribute()` and `setJsonAttribute()` now return `$this` (fluent) instead of `void`, consistent with Laravel 13 Eloquent contracts. Call chains that previously ignored the return value are unaffected.
- `mutateAttribute()` now returns explicit `null` instead of a bare `return;` for missing/JSON-operator keys.
- `getMutatedAttributes()` deduplicates merged attribute lists, so duplicate keys from parent and JSON columns will no longer appear twice.
- The global `array_get()` helper (removed in Laravel 9) has been replaced with `data_get()` internally. No public API change.

## Contributing

Pull requests are welcome. Please ensure all tests pass before submitting:

```bash
composer install
vendor/bin/phpunit tests
```

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
