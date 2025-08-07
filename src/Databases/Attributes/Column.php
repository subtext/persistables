<?php

namespace Subtext\Persistables\Databases\Attributes;

use Attribute;

/**
 * Column metadata for properties of persistable objects.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public string $name,
        public ?string $table = null,
        public bool $primary = false,
        public bool $readonly = false
    ) {}
}
