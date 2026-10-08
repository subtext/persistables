<?php

namespace Subtext\Persistables\Console\Schemas\Databases;

use Subtext\Persistables\Databases\Sql;
use Subtext\Persistables\Databases\Meta;

class Reader
{
    protected Introspector $schema;
    protected string $rootPath;

    public function __construct(Introspector $schema, string $root)
    {
        $this->schema   = $schema;
        $this->rootPath = $root;
    }

    public function getMetaCollection(Meta\Collection $runtime): Meta\Collection
    {
        foreach ($runtime as $class => $meta) {
            // get table
            $tableName = $meta->getTable()->name;
            // get columns
            // get indexes and constraints
        }
    }
}
