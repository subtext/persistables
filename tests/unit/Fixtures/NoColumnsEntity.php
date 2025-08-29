<?php

namespace Subtext\Persistables\Tests\Unit\Fixtures;

use Subtext\Persistables\Persistable;
use Subtext\Persistables\Databases\Attributes\Table;

#[Table(name: "no_columns", primaryKey: "id")]
class NoColumnsEntity extends Persistable
{
    // no #[Column] attributes at all

    public function jsonSerialize(): mixed
    {
        return [];
    }
}
