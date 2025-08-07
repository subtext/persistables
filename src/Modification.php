<?php

namespace Subtext\Persistables;

/**
 * Store data related to changes in persistable models. This will be used to
 * determine if a model has changed since last being stored, or retrieved from
 * the database.
 */
class Modification
{
    private string $name;
    private mixed $oldValue;
    private mixed $newValue;

    public function __construct(string $name, mixed $oldValue, mixed $newValue)
    {
        $this->name     = $name;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
    }

    /**
     * A utility creation method.
     *
     * @param string $name    The name of the modified property
     * @param mixed $oldValue The old value stores on the property
     * @param mixed $newValue The new value assigned to the property
     *
     * @return Modification
     */
    public static function from(string $name, mixed $oldValue, mixed $newValue): Modification
    {
        return new Modification($name, $oldValue, $newValue);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getOldValue(): mixed
    {
        return $this->oldValue;
    }

    /**
     * @return mixed
     */
    public function getNewValue(): mixed
    {
        return $this->newValue;
    }
}
