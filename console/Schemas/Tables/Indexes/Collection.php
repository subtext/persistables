<?php

namespace Subtext\Persistables\Console\Schemas\Tables\Indexes;

use InvalidArgumentException;
use Subtext\Persistables\Console\Schemas\Tables\Index;

class Collection extends \Subtext\Collections\Collection
{
    /**
     * @inheritDoc
     */
    protected function validate(mixed $value): void
    {
        if (!$value instanceof Index) {
            throw new InvalidArgumentException(sprintf(
                'The value must be an instance of %s, %s given',
                Index::class,
                get_class($value)
            ));
        }
    }
}
