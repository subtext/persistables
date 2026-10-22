<?php

namespace Subtext\Persistables\Console\Schemas\Attributes\Errors;

use InvalidArgumentException;
use Throwable;

class Collection extends \Subtext\Collections\Collection
{
    /**
     * @inheritDoc
     */
    protected function validate(mixed $value): void
    {
        if (! $value instanceof Throwable) {
            throw new InvalidArgumentException(sprintf(
                'Value must be an instance of \Throwable, %s given',
                gettype($value)
            ));
        }
    }
}
