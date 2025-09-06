<?php

namespace Subtext\Persistables\Tests\Unit\Fixtures;

use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Entity;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Persistable;

#[Table('owner_aggregates', 'aggregateId')]
class OwnerAggregate extends Persistable
{
    #[Column('aggregateId', primary: true)]
    protected ?int $id = null;

    #[Column]
    protected string $name = '';

    #[Entity]
    protected ?ChildEntity $child = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->modify('id', $id);
        $child = $this->getChild();
        if ($child !== null) {
            $child->setAggregateId($id);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->modify('name', $name);
    }

    public function getChild(): ?ChildEntity
    {
        return $this->child;
    }

    public function setChild(ChildEntity $child): void
    {
        $this->child = $child;
    }

    public function jsonSerialize(): mixed
    {
        return (object) [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'child' => $this->getChild(),
        ];
    }
}
