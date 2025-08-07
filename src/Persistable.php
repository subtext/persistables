<?php

namespace Subtext\Persistables;

use JsonSerializable;

abstract class Persistable implements JsonSerializable
{
    use Hydrator;
    protected ?Modifications\Collection $modified = null;

    public function getModified(): Modifications\Collection
    {
        $this->initModifications();
        return $this->modified;
    }

    public function resetModified(): void
    {
        $this->initModifications();
        $this->modified->empty();
    }

    public function rollbackModifications(): void
    {
        foreach ($this->modified as $modification) {
            $name = $modification->getName();
            $this->$name = $modification->getOldValue();
        }
        $this->modified->empty();
    }

    abstract public function jsonSerialize(): mixed;

    abstract public function getPersistables(): ?Collection;

    protected function modify(string $name, mixed $new): void
    {
        $this->initModifications();
        $old = $this->$name;
        $this->modified->append(Modification::from($name, $old, $new));
        $this->$name = $new;
    }

    protected function initModifications(): void
    {
        if ($this->modified === null) {
            $this->modified = new Modifications\Collection();
        }
    }
}
