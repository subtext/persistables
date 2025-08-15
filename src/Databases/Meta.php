<?php

namespace Subtext\Persistables\Databases;

use Subtext\Persistables\Databases\Attributes\Columns;
use Subtext\Persistables\Databases\Attributes\Joins;
use Subtext\Persistables\Databases\Attributes\Entities;
use Subtext\Persistables\Databases\Attributes\Table;

/**
 * Table, column, and relationship metadata for a persistable entity.
 */
class Meta
{
    private Table $table;
    private Columns\Collection $columns;
    private ?Joins\Collection $joins;
    private ?Entities\Collection $persistables;

    /**
     * @param Table $table
     * @param Columns\Collection $columns
     * @param Joins\Collection|null $joins
     * @param Entities\Collection|null $persistables
     */
    public function __construct(
        Table $table,
        Columns\Collection $columns,
        ?Joins\Collection $joins,
        ?Entities\Collection $persistables
    ) {
        $this->table        = $table;
        $this->columns      = $columns;
        $this->joins        = $joins;
        $this->persistables = $persistables;
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

    public function getPersistables(): ?Entities\Collection
    {
        return $this->persistables;
    }
}
