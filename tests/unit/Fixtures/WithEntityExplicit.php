<?php

namespace Subtext\Persistables\Tests\Unit\Fixtures;

use Subtext\Persistables\Databases\Attributes\PersistOrder;
use Subtext\Persistables\Persistable;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Entity;

#[Table(name: "with_entity_explicit", primaryKey: "id")]
class WithEntityExplicit extends Persistable
{
    #[Column(name: "id", primary: true)]
    protected ?int $id = null;

    #[Column('simple_entity_id')]
    protected ?int $entityId = null;

    #[Entity(class: SimpleEntity::class, foreign: "entityId", order: PersistOrder::BEFORE)]
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

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(int $entityId): void
    {
        $this->modify('entityId', $entityId);
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
