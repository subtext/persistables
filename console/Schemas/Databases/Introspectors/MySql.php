<?php

namespace Subtext\Persistables\Console\Schemas\Databases\Introspectors;

use Subtext\Persistables\Console\Schemas\Databases\Introspector;
use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Columns;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Meta;
use Subtext\Persistables\Databases\Sql;

class MySql implements Introspector
{
    protected Sql $db;

    public function __construct(Sql $db)
    {
        $this->db = $db;
    }

    public function getMeta(Meta $runtime): Meta
    {
        $table   = $runtime->getTable();
        $columns = $runtime->getColumns();
        return new Meta(
            $table,
            $columns,
            $runtime->getJoins(),
            $runtime->getPersistables()
        );
    }

    protected function getColumnSchema(Table $table)
    {
        $data = $this->db->getQueryData(
            <<<SQL
            SELECT `column_name`,
                   `data_type`,
                   `is_nullable`,
                   `column_default`
            FROM `information_schema`.`columns`
            WHERE `table_name` = ?
            SQL,
            [$table->name]
        );
        $columns = new Columns\Collection();
        foreach ($data as $column) {
            $columns->set($column->column_name, new Column(
                $column->column_name,
            ));
        }
    }
}
