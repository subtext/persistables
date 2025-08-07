<?php

namespace Subtext\Persistables\Queries;

use Subtext\Collections;

class Collection extends Collections\Collection
{
    protected function validate(mixed $value): void
    {
        if (!$value instanceof SqlCommand) {
            throw new \InvalidArgumentException(sprintf(
                'Value must be an instance of: %s',
                SqlCommand::class
            ));
        }
    }
}
