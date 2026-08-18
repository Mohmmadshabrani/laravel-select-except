<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Schema column cache
    |--------------------------------------------------------------------------
    |
    | Column listings are cached so selectExcept() does not hit the schema on
    | every query. The cache is flushed automatically after migrations. Use
    | `select-except:clear` if you change schema outside migrations.
    |
    */

    'cache' => [
        'enabled' => env('SELECT_EXCEPT_CACHE', true),
        'store' => env('SELECT_EXCEPT_CACHE_STORE'),
        'prefix' => 'select-except',
        'ttl' => env('SELECT_EXCEPT_CACHE_TTL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Throw when nothing would remain
    |--------------------------------------------------------------------------
    |
    | If true, selectExcept() throws when the omit list would leave zero
    | columns. That avoids silently falling back to SELECT * (which would
    | leak the columns you tried to hide). If false, the query is unchanged.
    |
    */

    'throw_when_empty' => true,

];
