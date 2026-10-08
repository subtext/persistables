<?php

namespace Subtext\Persistables\Console\Schemas\Tables;

readonly class Column
{
    public function __construct(
        protected string       $name,
        protected Columns\Type $type,
        protected bool         $primary,
        protected bool         $autoIncrement,
        protected bool         $nullable,
        protected mixed        $default,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): Columns\Type
    {
        return $this->type;
    }

    public function getPrimary(): bool
    {
        return $this->primary;
    }

    public function getAutoIncrement(): bool
    {
        return $this->autoIncrement;
    }

    public function getNullable(): bool
    {
        return $this->nullable;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }
}
