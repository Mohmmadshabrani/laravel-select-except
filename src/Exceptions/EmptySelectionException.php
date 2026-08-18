<?php

namespace Shabrani\SelectExcept\Exceptions;

use InvalidArgumentException;

class EmptySelectionException extends InvalidArgumentException
{
    public static function forTable(string $table): self
    {
        return new self(
            "Cannot drop every column from [{$table}]. At least one column must remain selected."
        );
    }
}
