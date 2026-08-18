<?php

namespace Shabrani\SelectExcept;

use Illuminate\Contracts\Database\Query\Expression as QueryExpression;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;
use Shabrani\SelectExcept\Exceptions\EmptySelectionException;

class SelectExcept
{
    public function __construct(protected ColumnListingCache $columns) {}

    /**
     * @param  array<int, mixed>  $columns
     */
    public function apply(EloquentBuilder|QueryBuilder $query, array $columns): EloquentBuilder|QueryBuilder
    {
        $except = $this->normalize($columns);

        if ($except === []) {
            return $query;
        }

        return $query instanceof EloquentBuilder
            ? $this->applyToEloquent($query, $except)
            : $this->applyToQuery($query, $except);
    }

    /**
     * @param  list<string>  $except
     */
    protected function applyToEloquent(EloquentBuilder $query, array $except): EloquentBuilder
    {
        $model = $query->getModel();
        $table = $model->getTable();
        $alias = $this->aliasFromFrom($query->getQuery()->from) ?? $table;
        $listing = $this->columns->get($model->getConnection(), $table);

        $keep = $this->resolve(
            $query->getQuery()->columns,
            $listing,
            $except,
            $table,
            $alias,
            fn (string $column) => "{$alias}.{$column}",
        );

        if ($keep === []) {
            return $this->emptyResult($table, $query);
        }

        return $query->select($keep);
    }

    /**
     * @param  list<string>  $except
     */
    protected function applyToQuery(QueryBuilder $query, array $except): QueryBuilder
    {
        [$table, $alias] = $this->parseFrom($query->from);
        $listing = $this->columns->get($query->getConnection(), $table);

        $keep = $this->resolve(
            $query->columns,
            $listing,
            $except,
            $table,
            $alias,
            fn (string $column) => "{$alias}.{$column}",
        );

        if ($keep === []) {
            return $this->emptyResult($table, $query);
        }

        return $query->select($keep);
    }

    /**
     * @param  list<mixed>|null  $existing
     * @param  list<string>  $listing
     * @param  list<string>  $except
     * @param  callable(string): string  $qualify
     * @return list<mixed>
     */
    protected function resolve(
        ?array $existing,
        array $listing,
        array $except,
        string $table,
        string $alias,
        callable $qualify,
    ): array {
        $exceptLookup = array_fill_keys($except, true);
        $listingKeep = array_values(array_filter(
            $listing,
            fn (string $column) => ! isset($exceptLookup[$column]),
        ));

        if ($this->selectsAll($existing, $table, $alias)) {
            $qualified = array_map($qualify, $listingKeep);
            $extras = array_values(array_filter(
                $existing ?? [],
                fn ($column) => ! $this->isWildcard($column, $table, $alias)
                    && ! $this->shouldDrop($column, $exceptLookup),
            ));

            return array_values([...$qualified, ...$extras]);
        }

        return array_values(array_filter(
            $existing ?? [],
            fn ($column) => ! $this->shouldDrop($column, $exceptLookup),
        ));
    }

    /**
     * @param  list<mixed>  $columns
     * @return list<string>
     */
    protected function normalize(array $columns): array
    {
        return array_values(array_unique(array_filter(array_map(
            function ($column) {
                $column = trim((string) $column);

                if (str_contains($column, '.')) {
                    $column = Str::afterLast($column, '.');
                }

                return $column;
            },
            Arr::flatten($columns),
        ))));
    }

    /**
     * @param  list<mixed>|null  $columns
     */
    protected function selectsAll(?array $columns, string $table, string $alias): bool
    {
        if ($columns === null || $columns === []) {
            return true;
        }

        foreach ($columns as $column) {
            if ($this->isWildcard($column, $table, $alias)) {
                return true;
            }
        }

        return false;
    }

    protected function isWildcard(mixed $column, string $table, string $alias): bool
    {
        if (! is_string($column)) {
            return false;
        }

        $column = trim($column, ' `"[]');

        return in_array($column, ['*', "{$table}.*", "{$alias}.*"], true);
    }

    /**
     * @param  array<string, true>  $exceptLookup
     */
    protected function shouldDrop(mixed $column, array $exceptLookup): bool
    {
        $name = $this->columnName($column);

        return $name !== null && isset($exceptLookup[$name]);
    }

    protected function columnName(mixed $column): ?string
    {
        if ($column instanceof QueryExpression) {
            return null;
        }

        if (! is_string($column)) {
            return null;
        }

        $column = trim($column, ' `"[]');
        $column = preg_replace('/\s+as\s+.+$/i', '', $column) ?? $column;

        if (str_contains($column, '.')) {
            $column = Str::afterLast($column, '.');
        }

        return $column === '' ? null : $column;
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function parseFrom(mixed $from): array
    {
        if (! is_string($from) || trim($from) === '') {
            throw new RuntimeException('Cannot apply selectExcept() unless the query has a string table name.');
        }

        $from = trim($from, ' `"[]');

        if (preg_match('/^(.+?)\s+as\s+(\S+)$/i', $from, $matches) === 1) {
            return [trim($matches[1], ' `"[]'), trim($matches[2], ' `"[]')];
        }

        if (preg_match('/^(\S+)\s+(\w+)$/', $from, $matches) === 1) {
            return [trim($matches[1], ' `"[]'), $matches[2]];
        }

        return [$from, $from];
    }

    protected function aliasFromFrom(mixed $from): ?string
    {
        if (! is_string($from) || trim($from) === '') {
            return null;
        }

        return $this->parseFrom($from)[1];
    }

    /**
     * @template T of EloquentBuilder|QueryBuilder
     *
     * @param  T  $query
     * @return T
     */
    protected function emptyResult(string $table, EloquentBuilder|QueryBuilder $query): EloquentBuilder|QueryBuilder
    {
        if (config('select-except.throw_when_empty', true)) {
            throw EmptySelectionException::forTable($table);
        }

        return $query;
    }
}
