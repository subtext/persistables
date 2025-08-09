<?php

namespace Subtext\Persistables\Databases\Attributes\Joins;

use Subtext\Collections;
use Subtext\Persistables\Databases\Attributes\Join;
use InvalidArgumentException;

class Collection extends Collections\Collection
{
    protected function validate(mixed $value): void
    {
        if (!$value instanceof Join) {
            throw new InvalidArgumentException(
                'Value must be an instance of ' . Join::class
            );
        }
    }
}
