

<?php

namespace Subtext\Persistables\Databases\Meta;

use InvalidArgumentException;
use Subtext\Collections;
use Subtext\Persistables\Databases\Meta;

class Collection extends Collections\Collection
{
    /**
     * @param string $id The corresponding property name from the persistable
     * @return Meta
     */
    public function get(string $id)
    {
        return parent::get($id);
    }

    protected function validate(mixed $value): void
    {
        if (!$value instanceof Meta) {
            throw new InvalidArgumentException(sprintf(
                'Value must be an instance of: %s',
                Meta::class
            ));
        }
    }
}
