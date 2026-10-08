<?php

namespace Subtext\Persistables\Console\Schemas\Tables\Columns;

use InvalidArgumentException;
use Subtext\Collections;
use Subtext\Persistables\Console\Schemas\Tables\Column;

class Collection extends Collections\Collection
{
    /**
     * @inheritDoc
     */
    protected function validate(mixed $value): void
    {
        if (!$value instanceof Column) {
            throw new InvalidArgumentException(sprintf(
                'Value must be an instance of "%s", but "%s" given.',
                Column::class,
                get_class($value)
            ));
        }
    }
}
