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
        $this->initModifications();
        return $this->modified;
    }

    /**
     * Delete all modification records.
     *
     * @return void
     */
    public function resetModified(): void
    {
        $this->initModifications();
        $this->modified->empty();
    }

    /**
     * Undo the modifications and remove the records.
     *
     * @return void
     */
    public function rollbackModifications(): void
    {
        foreach ($this->modified as $modification) {
            $name = $modification->getName();
            $this->$name = $modification->getOldValue();
        }
        $this->modified->empty();
    }

    /**
     * Define a method for storing your data as JSON.
     *
     * @return mixed
     */
    abstract public function jsonSerialize(): mixed;

    /**
     * Returns a collection of child persistable objects or null if none exist.
     *
     * @return Collection|null
     */
    abstract public function getPersistables(): ?Collection;

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
        $this->initModifications();
        $old = $this->$name;
        $this->modified->append(Modification::from($name, $old, $new));
        $this->$name = $new;
    }

    /**
     * Ensure the modifications collection exists.
     *
     * @return void
     */
    protected function initModifications(): void
    {
        if ($this->modified === null) {
            $this->modified = new Modifications\Collection();
        }
    }
}
