<?php

namespace Subtext\Persistables\Tests\Unit\Fixtures;

use Subtext\Persistables\Persistable;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Entity;

#[Table(name: "with_entity_explicit", primaryKey: "id")]
class WithEntityExplicit extends Persistable
{
    #[Column(name: "id", primary: true)]
    protected ?int $id = null;

    #[Entity(class: SimpleEntity::class, foreign: "simple_entity_id")]
    protected SimpleEntity $child;

    public function __construct()
    {
        $this->child = new SimpleEntity();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->modify('id', $id);
    }

    public function getChild(): SimpleEntity
    {
        return $this->child;
    }

    public function setChild(SimpleEntity $child): void
    {
        $this->modify('child', $child);
    }

    public function jsonSerialize(): mixed
    {
        return ['id' => $this->id, 'child' => $this->child->jsonSerialize()];
    }
}
