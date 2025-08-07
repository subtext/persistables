<?php

namespace Subtext\Persistables;

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

    public static function from(string $name, mixed $oldValue, mixed $newValue): Modification
    {
        return new Modification($name, $oldValue, $newValue);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getOldValue(): mixed
    {
        return $this->oldValue;
    }

    public function getNewValue(): mixed
    {
        return $this->newValue;
    }
}
