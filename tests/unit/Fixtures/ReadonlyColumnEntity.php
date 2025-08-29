<?php

namespace Subtext\Persistables\Tests\Unit\Fixtures;

use Subtext\Persistables\Persistable;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Attributes\Column;

#[Table(name: "readonly_test", primaryKey: "id")]
class ReadonlyColumnEntity extends Persistable
{
    #[Column(name: "id", primary: true)]
    protected ?int $id = null;

    #[Column(name: "created_at", readonly: true)]
    protected string $createdAt = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->modify('id', $id);
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
    // no setter expected (readonly)

    public function jsonSerialize(): mixed
    {
        return ['id' => $this->id, 'createdAt' => $this->createdAt];
    }
}
