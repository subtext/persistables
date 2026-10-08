<?php

namespace Subtext\Persistables\Console\Schemas\Databases\Meta;

use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Columns;
use Subtext\Persistables\Databases\Attributes\Joins;
use Subtext\Persistables\Databases\Attributes\Entities;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Meta;
use Subtext\Persistables\Databases\Meta\Collection;
use Subtext\Persistables\Databases\Sql;

class Factory
{
    private static ?self $instance = null;
    private Collection $meta;
    private Sql $db;

    private function __construct(Sql $db)
    {
        $this->meta = new Collection();
        $this->db   = $db;
    }

    public static function getInstance(Sql $db, bool $new = false): self
    {
        if (self::$instance === null || $new) {
            self::$instance = new self($db);
        }
        return self::$instance;
    }

    public function get(string $tableName): ?Meta
    {
        if (!$this->meta->has($tableName)) {
            $table = new Table(
                $tableName,
                $this->getPrimaryKey($tableName)
            );
            $columns = new Columns\Collection();
            foreach ($this->getColumns($tableName) as $column) {
                $columns->set($column->name, new Column(
                    $column->name,
                    $table->name,
                    false,
                    false,
                    null,
                    null,
                    $column->type,
                    $column->length,
                    $column->nullable,
                    $column->increment,
                    $column->precision,
                    $column->scale,
                    $column->unique === 1 ? true : null,
                    $column->uqGroup,
                    $column->index === 1 ? true : null,
                    $column->idxGroup,
                    $column->unsigned,
                    $column->nullDefault,
                    $column->default
                ));
            }
            $this->meta->set($tableName, new Meta(
                $table,
                $columns,
                null,
                null
            ));
        }
        return $this->meta->get($tableName);
    }

    public function getMeta(): Collection
    {
        return $this->meta;
    }
}
