<?php

namespace Subtext\Persistables\Tests\Unit\Fixtures;

use Subtext\Persistables\Persistable;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Entity;

#[Table(name: "with_entity_union", primaryKey: "id")]
class WithEntityUnion extends Persistable
{
    #[Column(name: "id", primary: true)]
    protected ?int $id = null;

    // No class given; inferred from union type (SimpleEntity|int)
    #[Entity(foreign: "simple_entity_id")]
    protected SimpleEntity|int $child;

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

    public function getChild(): SimpleEntity|int
    {
        return $this->child;
    }

    public function setChild(SimpleEntity|int $child): void
    {
        $this->modify('child', $child);
    }

    public function jsonSerialize(): mixed
    {
        return ['id' => $this->id, 'child' => is_object($this->child) ? $this->child->jsonSerialize() : $this->child];
    }
}
