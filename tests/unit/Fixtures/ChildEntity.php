<?php

namespace Subtext\Persistables\Tests\Unit\Fixtures;

use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Persistable;

#[Table('child_entities', 'id')]
class ChildEntity extends Persistable
{
    #[Column]
    protected ?int $id = null;

    #[Column]
    protected string $name = '';

    #[Column]
    protected int $aggregateId = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->modify('id', $id);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->modify('name', $name);
    }

    public function getAggregateId(): int
    {
        return $this->aggregateId;
    }

    public function setAggregateId(int $aggregateId): void
    {
        $this->modify('aggregateId', $aggregateId);
    }

    public function jsonSerialize(): mixed
    {
        return (object) [
            'id'          => $this->getId(),
            'name'        => $this->getName(),
            'aggregateId' => $this->getAggregateId(),
        ];
    }
}
