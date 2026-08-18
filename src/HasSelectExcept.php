<?php

namespace Shabrani\SelectExcept;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * Opt in to default SQL-level column omissions on a model.
 *
 * @property list<string> $selectExcept
 *
 * @method static Builder selectExcept(string|array ...$columns)
 * @method static Builder selectExceptHidden()
 * @method static Builder withAllColumns()
 * @method static Builder including(string|array ...$columns)
 * @method Builder selectExcept(string|array ...$columns)
 * @method Builder selectExceptHidden()
 * @method Builder withAllColumns()
 * @method Builder including(string|array ...$columns)
 */
trait HasSelectExcept
{
    public static function bootHasSelectExcept(): void
    {
        static::addGlobalScope('selectExcept', function (Builder $builder): void {
            $except = $builder->getModel()->getSelectExcept();

            if ($except === []) {
                return;
            }

            $builder->selectExcept(...$except);
        });
    }

    /**
     * @param  string|list<string>  ...$columns
     */
    public function scopeSelectExcept(Builder $query, ...$columns): Builder
    {
        return app(SelectExcept::class)->apply($query, $columns);
    }

    public function scopeWithAllColumns(Builder $query): Builder
    {
        return $query->withoutGlobalScope('selectExcept');
    }

    /**
     * @param  string|list<string>  ...$columns
     */
    public function scopeIncluding(Builder $query, ...$columns): Builder
    {
        $query->withoutGlobalScope('selectExcept');

        $restore = array_values(array_filter(array_map(
            'trim',
            Arr::flatten($columns),
        )));

        if ($restore === [] || $restore === ['*']) {
            return $query;
        }

        $still = array_values(array_diff($this->getSelectExcept(), $restore));

        return $still === [] ? $query : $query->selectExcept(...$still);
    }

    public function scopeSelectExceptHidden(Builder $query): Builder
    {
        $hidden = $this->getHidden();

        return $hidden === [] ? $query : $query->selectExcept(...$hidden);
    }

    /**
     * @return list<string>
     */
    public function getSelectExcept(): array
    {
        return array_values(array_unique(array_filter(array_map(
            'trim',
            Arr::flatten($this->selectExcept ?? []),
        ))));
    }

    /**
     * Column names for this model's table (cached).
     *
     * Inspired by ManojKiran's approach of reading the schema instead of
     * maintaining a hardcoded $columns property:
     * https://stackoverflow.com/a/56425794
     *
     * @return list<string>
     */
    public function getTableColumns(): array
    {
        return app(ColumnListingCache::class)->get(
            $this->getConnection(),
            $this->getTable(),
        );
    }
}
