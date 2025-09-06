<?php

namespace Subtext\Persistables\Tests\Unit\Fixtures;

use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Entity;
use Subtext\Persistables\Databases\Attributes\PersistOrder;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Persistable;

#[Table('complex_aggregates', 'id')]
class ComplexAggregate extends Persistable
{
    #[Column]
    protected ?int $id = null;

    #[Column]
    protected ?int $entityId = null;

    #[Entity(foreign: 'entityId', nullable: true, order: PersistOrder::BEFORE)]
    protected SimpleEntity $entity;

    #[Entity(foreign: 'aggregateId')]
    protected Children $children;

    public function __construct()
    {
        $this->children = new Children();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->modify('id', $id);
        foreach ($this->getChildren() as $child) {
            $child->setAggregateId($id);
        }
        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): self
    {
        $this->modify('entityId', $entityId);
        return $this;
    }

    public function getEntity(): ?SimpleEntity
    {
        return $this->entity;
    }

    public function setEntity(?SimpleEntity $entity): self
    {
        $this->entity = $entity;
        if ($entity !== null) {
            $this->modify('entityId', $entity->getId());
        }
        return $this;
    }

    public function getChildren(): Children
    {
        return $this->children;
    }

    public function setChildren(Children $children): self
    {
        $this->children = $children;
        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return (object) [
            'id'       => $this->getId(),
            'entityId' => $this->getEntityId(),
        ];
    }
}
