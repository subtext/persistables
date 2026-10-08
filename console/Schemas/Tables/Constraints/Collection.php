<?php

namespace Subtext\Persistables\Console\Schemas\Tables\Constraints;

class Collection extends \Subtext\Collections\Collection
{
    /**
     * @inheritDoc
     */
    protected function validate(mixed $value): void
    {
        if (!($value instanceof Foreign || $value instanceof Unique)) {
            throw new \InvalidArgumentException(sprintf(
                'The value must be an instance of "%s" or "%s, "%s" given',
                Foreign::class,
                Unique::class,
                get_class($value)
            ));
        }
    }
}
