<?php

namespace Shabrani\SelectExcept\Mixins;

use Closure;
use Illuminate\Database\Query\Builder;
use Shabrani\SelectExcept\SelectExcept;

/**
 * @mixin Builder
 */
class QueryBuilderMixin
{
    public function selectExcept(): Closure
    {
        return function (string|array ...$columns) {
            return app(SelectExcept::class)->apply($this, $columns);
        };
    }
}
