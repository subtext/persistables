<?php

namespace Subtext\Persistables;

use InvalidArgumentException;
use Subtext\Collections;

abstract class Collection extends Collections\Collection
{
    abstract public function getEntityClass(): string;

    protected function validate(mixed $value): void
    {
        if (!is_object($value) || !($value::class === $this->getEntityClass())) {
            throw new InvalidArgumentException(sprintf(
                "Value must be an instance of %s",
                $this->getEntityClass()
            ));
        }
    }
}
