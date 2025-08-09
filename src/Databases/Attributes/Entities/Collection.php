<?php

namespace Subtext\Persistables\Databases\Attributes\Entities;

use Subtext\Persistables\Databases\Attributes\Entity;
use InvalidArgumentException;

class Collection extends \Subtext\Collections\Collection
{
    protected function validate(mixed $value): void
    {
        if (!$value instanceof Entity) {
            throw new InvalidArgumentException(sprintf(
                'Value must be an instance of %s',
                Entity::class
            ));
        }
    }
}
