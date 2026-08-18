<?php

namespace Shabrani\SelectExcept\Mixins;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Shabrani\SelectExcept\SelectExcept;

/**
 * @mixin Builder
 */
class EloquentBuilderMixin
{
    public function selectExcept(): Closure
    {
        return function (string|array ...$columns) {
            return app(SelectExcept::class)->apply($this, $columns);
        };
    }

    public function selectExceptHidden(): Closure
    {
        return function () {
            $hidden = $this->getModel()->getHidden();

            if ($hidden === []) {
                return $this;
            }

            return $this->selectExcept(...$hidden);
        };
    }

    public function withAllColumns(): Closure
    {
        return function () {
            return $this->withoutGlobalScope('selectExcept');
        };
    }

    public function including(): Closure
    {
        return function (...$columns) {
            $this->withoutGlobalScope('selectExcept');

            $restore = array_values(array_filter(array_map(
                'trim',
                Arr::flatten($columns),
            )));

            if ($restore === [] || $restore === ['*']) {
                return $this;
            }

            $defaults = method_exists($this->getModel(), 'getSelectExcept')
                ? $this->getModel()->getSelectExcept()
                : [];

            $still = array_values(array_diff($defaults, $restore));

            return $still === [] ? $this : $this->selectExcept(...$still);
        };
    }
}
