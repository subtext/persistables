<?php

namespace Subtext\Persistables\Tests\Unit\Fixtures;

use Subtext\Persistables\Persistable;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Entity;

#[Table(name: "with_entity_implicit_nullable", primaryKey: "id")]
class WithEntityImplicitNullable extends Persistable
{
    #[Column(name: "id", primary: true)]
    protected ?int $id = null;

    // class inferred from property type; allowsNull() => true
    #[Entity(foreign: "simple_entity_id")]
    protected ?SimpleEntity $child = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->modify('id', $id);
    }

    public function getChild(): ?SimpleEntity
    {
        return $this->child;
    }

    public function setChild(?SimpleEntity $child): void
    {
        $this->modify('child', $child);
    }

    public function jsonSerialize(): mixed
    {
        return ['id' => $this->id, 'child' => $this->child?->jsonSerialize()];
    }
}
