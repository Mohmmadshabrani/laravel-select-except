<?php

namespace Shabrani\SelectExcept;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\Cache;

/**
 * Cached schema column listings.
 *
 * Based on ManojKiran's idea of caching getColumnListing() so models do not
 * need a hardcoded $columns property: https://stackoverflow.com/a/56425794
 *
 * Cache is versioned and flushed after migrations, rather than keyed off
 * filemtime() of the migrations directory (which misses in-file edits).
 */
class ColumnListingCache
{
    /**
     * @return list<string>
     */
    public function get(ConnectionInterface $connection, string $table): array
    {
        $ttl = $this->ttl();

        if ($ttl === false) {
            return $this->list($connection, $table);
        }

        $callback = fn () => $this->list($connection, $table);
        $key = $this->key($connection, $table);

        return $ttl === null
            ? $this->cache()->rememberForever($key, $callback)
            : $this->cache()->remember($key, $ttl, $callback);
    }

    public function flush(): void
    {
        $this->cache()->forever($this->versionKey(), $this->version() + 1);
    }

    /**
     * @return list<string>
     */
    protected function list(ConnectionInterface $connection, string $table): array
    {
        return array_values($connection->getSchemaBuilder()->getColumnListing($table));
    }

    protected function key(ConnectionInterface $connection, string $table): string
    {
        return implode(':', [
            $this->prefix(),
            $this->version(),
            $connection->getName(),
            (string) $connection->getDatabaseName(),
            $table,
        ]);
    }

    protected function version(): int
    {
        return (int) $this->cache()->get($this->versionKey(), 1);
    }

    protected function versionKey(): string
    {
        return $this->prefix().':version';
    }

    protected function prefix(): string
    {
        return (string) config('select-except.cache.prefix', 'select-except');
    }

    protected function ttl(): \DateTimeInterface|\DateInterval|int|null|false
    {
        if (! config('select-except.cache.enabled', true)) {
            return false;
        }

        $ttl = config('select-except.cache.ttl');

        return $ttl === null || $ttl === '' ? null : (int) $ttl;
    }

    protected function cache(): CacheRepository
    {
        $store = config('select-except.cache.store');

        return $store ? Cache::store($store) : Cache::store();
    }
}
