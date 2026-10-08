<?php

namespace Subtext\Persistables\Console\Schemas\Tables\Constraints;

readonly class Unique
{
    public function __construct(
        public string $name,
        public string $columns,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getColumns(): string
    {
        return $this->columns;
    }
}
