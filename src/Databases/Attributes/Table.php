<?php

namespace Subtext\Persistables\Databases\Attributes;

use Attribute;

/**
 * Table metadata for persistable objects.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Table
{
    public function __construct(
        public string $name,
        public ?string $primaryKey = null
    ) {
    }
}
