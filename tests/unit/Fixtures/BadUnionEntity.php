<?php

namespace Subtext\Persistable\Tests\Unit\Fixtures;

use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Entity;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Persistable;

#[Table(name: "bad_union", primaryKey: "id")]
class BadUnionEntity extends Persistable
{
    #[Column(name: "id", primary: true)]
    protected ?int $id = null;

    #[Entity(foreign: "x_id")]
    protected int|string $x;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->modify("id", $id);
    }

    public function getX(): int|string
    {
        return $this->x;
    }

    public function setX(int|string $x): void
    {
        $this->modify("x", $x);
    }

    public function jsonSerialize(): mixed
    {
        return [
            "id" => $this->id,
            "x"  => $this->x
        ];
    }
}
