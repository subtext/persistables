<?php

namespace Subtext\Persistables;

use JsonSerializable;

abstract class Persistable implements JsonSerializable
{
    use Hydrator;
    protected ?Modifications\Collection $modified = null;

    /**
     * Returns a collection of modifications, if any exist.
     *
     * @return Modifications\Collection
     */
    public function getModified(): Modifications\Collection
    {
        if ($this->modified === null) {
            $this->modified = new Modifications\Collection();
        }
        return $this->modified;
    }

    /**
     * Delete all modification records.
     *
     * @return void
     */
    public function resetModified(): void
    {
        $this->getModified()->empty();
    }

    /**
     * Undo the modifications and remove the records.
     *
     * @return void
     */
    public function rollbackModifications(): void
    {
        foreach ($this->modified as $modification) {
            $name        = $modification->getName();
            $this->$name = $modification->getOldValue();
        }
        $this->resetModified();
    }

    /**
     * Define a method for storing your data as JSON.
     *
     * @return mixed
     */
    abstract public function jsonSerialize(): mixed;

    /**
     * A utility method applied in setters, which tracks changes over time.
     *
     * @param string $name
     * @param mixed $new
     *
     * @return void
     */
    protected function modify(string $name, mixed $new): void
    {
        $old = $this->$name;
        if ($old !== $new) {
            $this->getModified()->append(Modification::from($name, $old, $new));
            $this->$name = $new;
        }
    }
}
