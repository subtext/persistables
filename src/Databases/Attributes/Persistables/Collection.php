<?php

namespace Subtext\Persistables\Databases\Attributes\Persistables;

use Subtext\Persistables\Databases\Attributes\Persistable;

class Collection extends \Subtext\Collections\Collection
{
    protected function validate(mixed $value): void
    {
        if (!$value instanceof Persistable) {
            throw new \InvalidArgumentException(sprintf(
                'Value must be an instance of %s',
                Persistable::class
            ));
        }
    }
}
