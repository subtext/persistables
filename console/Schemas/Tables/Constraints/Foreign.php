<?php

namespace Subtext\Persistables\Console\Schemas\Tables\Constraints;

readonly class Foreign
{
    public function __construct(
        private string $name,
        private string $column,
        private string $table,
        private string $reference,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getReference(): string
    {
        return $this->reference;
    }
}
