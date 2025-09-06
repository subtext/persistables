<?php

namespace Subtext\Persistables\Tests\Unit\Fixtures;

use Subtext\Persistables\Collection;

class Children extends Collection
{
    /**
     * Returns a ChildEntity instance
     *
     * @param string $id
     *
     * @return ChildEntity|null
     */
    public function get(string $id)
    {
        return parent::get($id);
    }

    public function getEntityClass(): string
    {
        return ChildEntity::class;
    }
}
