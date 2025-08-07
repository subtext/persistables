<?php

namespace Subtext\Persistables;

use InvalidArgumentException;
use Subtext\Collections;

abstract class Collection extends Collections\Collection
{
    /**
     * Returns the FQDN class name of the entity to be stored within the
     * collection. This value is also provided to the validate method.
     *
     * @return string
     */
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
