<?php

namespace Subtext\Persistables\Databases\Attributes\Columns;

use Subtext\Collections;
use Subtext\Persistables\Databases\Attributes\Column;
use InvalidArgumentException;

class Collection extends Collections\Collection
{
    protected function validate(mixed $value): void
    {
        if (!$value instanceof Column) {
            throw new InvalidArgumentException(sprintf(
                'Value must be an instance of: %s',
                Column::class
            ));
        }
    }
}
