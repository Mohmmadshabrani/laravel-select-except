# Laravel Select Except

[![Tests](https://github.com/Mohmmadshabrani/laravel-select-except/actions/workflows/tests.yml/badge.svg)](https://github.com/Mohmmadshabrani/laravel-select-except/actions)

Omit columns from Laravel `SELECT` statements **at the SQL level**.

`$hidden` still fetches the data and only strips it when serializing. This package never selects those columns — the same idea as SQL `SELECT * EXCEPT (...)`. Use it for passwords, tokens, and heavy `text`/`json` fields on index queries.

```php
User::selectExcept('password', 'remember_token')->get();
Post::selectExcept('body')->paginate();
```

Works on every Eloquent query after install. No model changes required.

## Install

```bash
composer require mohmmadshabrani/laravel-select-except
```

Laravel auto-discovers the service provider. Publish the config only if you want to change cache or error behavior:

```bash
php artisan vendor:publish --tag=select-except-config
```

## Usage

### One-off

Use anywhere you have an Eloquent builder — models, relations, eager loads, API index endpoints:

```php
User::selectExcept('password', 'remember_token')->get();
User::selectExcept(['password', 'remember_token'])->first();

// Already selected columns are filtered, not replaced
User::select('id', 'name', 'email', 'password')
    ->selectExcept('password')
    ->get();
// SELECT id, name, email

// Joins stay unambiguous: columns are qualified (`users.email`)
User::selectExcept('password')
    ->join('posts', 'posts.user_id', '=', 'users.id')
    ->get();

User::with([
    'posts' => fn ($posts) => $posts->selectExcept('body', 'html'),
])->get();

$user->posts()->selectExcept('body')->paginate();
```

Skip `$hidden` columns for read/API queries (do **not** do this on login — you still need `password` to verify the hash):

```php
User::selectExceptHidden()->get();
```

### Query builder

```php
DB::table('users')->selectExcept('password')->get();
DB::table('users as u')->selectExcept('password')->get();
```

### Default omissions on a model

For models with bulky columns, put the trait on the model and list columns once. List endpoints stay cheap; detail endpoints opt back in.

```php
use Shabrani\SelectExcept\HasSelectExcept;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasSelectExcept;

    protected array $selectExcept = [
        'body',
        'html',
        'search_index',
    ];
}
```

```php
Post::paginate();                         // body/html/search_index not selected
Post::withAllColumns()->find($id);        // full row for an edit/show page
Post::including('body')->find($id);       // body back, html + search_index still out
Post::including('body', 'html')->find($id);
```

Recommended: add the trait to your app base model so `selectExcept()`, `withAllColumns()`, and `including()` autocomplete everywhere, and subclasses can set `$selectExcept` when needed.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Shabrani\SelectExcept\HasSelectExcept;

abstract class Model extends BaseModel
{
    use HasSelectExcept;
}
```

### Typical API controller

```php
class PostController
{
    public function index()
    {
        return Post::query()
            ->latest()
            ->paginate();
    }

    public function show(Post $post)
    {
        return Post::including('body', 'html')->findOrFail($post->id);
    }
}
```

### Combine with Laravel `when`

```php
Post::query()
    ->when($request->boolean('lite'), fn ($query) => $query->selectExcept('body', 'html'))
    ->get();
```

## Why not `$hidden`?

| | `$hidden` | `selectExcept()` |
|---|---|---|
| Still selected from MySQL/Postgres | Yes | No |
| Present on `getAttributes()` / `toJson()` internals | Yes | No |
| Helps list-query performance (skips large TEXT/JSON) | No | Yes |
| Stops accidental logging of secrets loaded on the model | No | Yes |

Use both: `$hidden` for serialization safety, `selectExcept()` / `$selectExcept` so the database never sends the bytes.

## Caching

Column listings are cached so `selectExcept()` does not describe the table on every query.

- Cache is **forever** by default (`ttl` `null`)
- Flushed automatically after migrations (`MigrationsEnded`, `DatabaseRefreshed`)
- Connection + database + table are part of the key

```bash
php artisan select-except:clear
```

```env
SELECT_EXCEPT_CACHE=true
SELECT_EXCEPT_CACHE_STORE=redis
SELECT_EXCEPT_CACHE_TTL=
```

Set `ttl` to seconds if you prefer a sliding refresh instead of forever.

## Config

```php
// config/select-except.php

'throw_when_empty' => true,
```

If an omit list would leave **zero** columns, the package throws `EmptySelectionException` instead of falling back to `SELECT *` (which would leak the columns you tried to hide). Set this to `false` to leave the query unchanged.

## Notes

- Unknown column names are ignored.
- Raw expressions (`selectRaw`, `DB::raw`) are never stripped.
- `selectExcept('users.password')` and `selectExcept('password')` are the same.
- Do not omit the primary key if you still need `chunk()`, `cursor()`, or route-model binding on the result.
- Login/register queries that hash-check `password` must not omit it.
- `$model->password` after `selectExcept('password')` is `null` (or an exception if you enable `Model::preventAccessingMissingAttributes()`). That is expected: it was never selected.

## Testing this package

```bash
composer install
composer test
```

## Credits

The core idea — omit columns with `array_diff` against the table schema, instead of a hardcoded `$columns` list — comes from Stack Overflow:

- [Razor](https://stackoverflow.com/questions/23612221/how-to-exclude-certains-columns-while-using-eloquent) (accepted answer) showed `scopeExclude()` with `array_diff($this->columns, …)`. That works, but every model has to keep `$columns` in sync with the table.
- [ManojKiran](https://stackoverflow.com/a/56425794) skipped `$columns` entirely: `getColumnListing()` plus a cached `getTableColumns()`. That is the approach this package packages up.

This package keeps that schema-based listing and adds qualified columns, query-builder support, migration-aware cache flushing, and `$selectExcept` only as an optional *default omit list* (not a full column inventory).

## License

MIT

