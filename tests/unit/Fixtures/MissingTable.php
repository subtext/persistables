<?php

namespace Subtext\Persistables\Tests\Unit\Fixtures;

use Subtext\Persistables\Persistable;

class MissingTable extends Persistable
{
    protected ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->modify('id', $id);
    }

    public function jsonSerialize(): mixed
    {
        return ['id' => $this->id];
    }
}
