<?php

namespace Subtext\Persistables\Databases;

use Subtext\Persistables\Databases\Attributes\Joins;
use Subtext\Persistables\Databases\Attributes\Columns;
use Subtext\Persistables\Databases\Attributes\Table;

/**
 * Table and column metadata for a persistable entity
 */
class Meta
{
    private Table $table;
    private Columns\Collection $columns;
    private ?Joins\Collection $joins;

    public function __construct(
        Table $table,
        Columns\Collection $columns,
        ?Joins\Collection $joins
    ) {
        $this->table   = $table;
        $this->columns = $columns;
        $this->joins   = $joins;
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function getColumns(): Columns\Collection
    {
        return $this->columns;
    }

    public function getJoins(): ?Joins\Collection
    {
        return $this->joins;
    }
}
