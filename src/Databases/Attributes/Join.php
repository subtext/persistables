<?php

namespace Subtext\Persistables\Databases\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Join
{
    public function __construct(
        public string $type,
        public string $table,
        public string $key,
        public ?string $foreign = null
    ) {
    }
}
