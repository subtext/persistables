<?php

namespace Subtext\Persistables\Tests\Unit\Fixtures;

use Subtext\Persistables\Persistable;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Attributes\Column;

#[Table(name: "custom_accessor", primaryKey: "id")]
class WithCustomAccessor extends Persistable
{
    #[Column(name: "id", primary: true, getter: "fetchId", setter: "storeId")]
    protected ?int $id = null;

    public function fetchId(): ?int
    {
        return $this->id;
    }

    public function storeId(?int $id): void
    {
        $this->modify('id', $id);
    }

    public function jsonSerialize(): mixed
    {
        return ['id' => $this->id];
    }
}
