<?php

namespace Subtext\Persistables\Modifications;

use InvalidArgumentException;
use Subtext\Collections;
use Subtext\Persistables\Modification;

class Collection extends Collections\Collection
{
    /**
     * @param string $id
     * @return Modification
     */
    public function get(string $id)
    {
        return parent::get($id);
    }

    /**
     * Get a list of the names of the modified properties
     *
     * @return Collections\Text|null
     */
    public function getNames(): ?Collections\Text
    {
        $names = null;
        if ($this->count() > 0) {
            $names = new Collections\Text();
            foreach ($this as $modification) {
                $value = $modification->getName();
                $names->set($value, $value);
            }
        }
        return $names;
    }

    protected function validate(mixed $value): void
    {
        if (!($value instanceof Modification)) {
            throw new InvalidArgumentException(sprintf(
                'Value must be instance of %s',
                Modification::class
            ));
        }
    }
}
