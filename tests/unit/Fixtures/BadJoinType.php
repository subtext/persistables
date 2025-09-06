<?php

namespace Subtext\Persistables\Tests\Unit\Fixtures;

use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Join;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Persistable;

#[Table(name: 'bad_join_type', primaryKey: 'id')]
#[Join('FOOBAR', 'other_table', 'id')]
class BadJoinType extends Persistable
{
    #[Column()]
    private ?int $id = null;

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
        return (object) [
            'id' => $this->getId(),
        ];
    }
}
