<?php

namespace Subtext\Persistables\Tests\Unit\Fixtures;

use Subtext\Persistables\Persistable;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Attributes\Column;

#[Table(name: "bad_accessor", primaryKey: "id")]
class BadAccessorEntity extends Persistable
{
    // setter name doesn't exist -> should throw
    #[Column(name: "id", primary: true, setter: "nopeSet")]
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function jsonSerialize(): mixed
    {
        return ['id' => $this->id];
    }
}
