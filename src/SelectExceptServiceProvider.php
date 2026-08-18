<?php

namespace Shabrani\SelectExcept;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Events\DatabaseRefreshed;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Shabrani\SelectExcept\Commands\ClearCacheCommand;
use Shabrani\SelectExcept\Mixins\EloquentBuilderMixin;
use Shabrani\SelectExcept\Mixins\QueryBuilderMixin;

class SelectExceptServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/select-except.php',
            'select-except',
        );

        $this->app->singleton(ColumnListingCache::class);
        $this->app->singleton(SelectExcept::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/select-except.php' => config_path('select-except.php'),
            ], 'select-except-config');

            $this->commands([
                ClearCacheCommand::class,
            ]);
        }

        EloquentBuilder::mixin(new EloquentBuilderMixin);
        QueryBuilder::mixin(new QueryBuilderMixin);

        $flush = fn () => $this->app->make(ColumnListingCache::class)->flush();

        Event::listen(MigrationsEnded::class, $flush);

        if (class_exists(DatabaseRefreshed::class)) {
            Event::listen(DatabaseRefreshed::class, $flush);
        }
    }
}
