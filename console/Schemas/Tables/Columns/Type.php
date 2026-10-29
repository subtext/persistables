<?php

namespace Subtext\Persistables\Console\Schemas\Tables\Columns;

use Subtext\Persistables\Databases\Attributes\Columns\Type as SqlLogicalType;

readonly class Type
{
    public function __construct(
        private SqlLogicalType $type,
        private ?int $length,
        private ?int $precision,
        private ?int $scale,
    ) {
    }

    public function getType(): SqlLogicalType
    {
        return $this->type;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    public function getScale(): ?int
    {
        return $this->scale;
    }
}
