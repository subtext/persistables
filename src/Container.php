<?php

namespace Subtext\Persistables;

use InvalidArgumentException;

class Container extends Collection {
    public function getEntityClass(): string
    {
        return Collection::class;
    }

    protected function validate(mixed $value): void
    {
        if (!($value instanceof Collection || $value instanceof Persistable)) {
            throw new InvalidArgumentException(sprintf(
                'Value must be an instance of %s or %s',
                Collection::class,
                Persistable::class
            ));
        }
    }
};