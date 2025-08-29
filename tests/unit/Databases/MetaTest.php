<?php

namespace Subtext\Persistables\Tests\Unit\Databases;

use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Databases\Attributes\Columns;
use Subtext\Persistables\Databases\Attributes\Entities;
use Subtext\Persistables\Databases\Attributes\Joins;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Meta;

class MetaTest extends TestCase
{
    public function testCanSetAndGetProperties(): void
    {
        $table    = $this->createMock(Table::class);
        $columns  = $this->createMock(Columns\Collection::class);
        $joins    = $this->createMock(Joins\Collection::class);
        $entities = $this->createMock(Entities\Collection::class);

        $unit = new Meta($table, $columns, $joins, $entities);

        $this->assertSame($table, $unit->getTable());
        $this->assertSame($columns, $unit->getColumns());
        $this->assertSame($joins, $unit->getJoins());
        $this->assertSame($entities, $unit->getPersistables());
    }
}
