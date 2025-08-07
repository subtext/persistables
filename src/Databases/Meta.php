<?php

namespace Subtext\Persistables\Databases;

use Subtext\Persistables\Databases\Attributes\Columns;
use Subtext\Persistables\Databases\Attributes\Table;

/**
 * Table and column metadata for a persistable entity
 */
class Meta
{
    private Table $table;
    private Columns\Collection $columns;

    public function __construct(Table $table, Columns\Collection $columns)
    {
        $this->table   = $table;
        $this->columns = $columns;
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function getColumns(): Columns\Collection
    {
        return $this->columns;
    }
}
