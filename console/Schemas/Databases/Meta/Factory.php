<?php

namespace Subtext\Persistables\Console\Schemas\Databases\Meta;

use Subtext\Persistables\Console\Schemas\Databases\Introspectors\Introspector;
use Subtext\Persistables\Databases\Meta\Collection;

class Factory
{
    private static ?self $instance = null;
    private Collection $meta;
    private Introspector $introspector;

    private function __construct(Introspector $introspector)
    {
        $this->meta         = new Collection();
        $this->introspector = $introspector;
    }

    public static function getInstance(Introspector $db, bool $new = false): self
    {
        if (self::$instance === null || $new) {
            self::$instance = new self($db);
        }
        return self::$instance;
    }

    public function get(Collection $schema): Collection
    {
        foreach ($schema as $ns => $runtime) {
            if (!$this->meta->has($ns)) {
                $this->meta->set($ns, $this->introspector->getMeta($runtime));
            }
        }
        return new Collection($this->meta->getArrayCopy());
    }

    //    private function getColumns(Meta $meta): Columns\Collection
    //    {
    //        $columns = new Columns\Collection();
    //        foreach ([] as $column) {
    //            $columns->set($column->name, new Column(
    //                $column->name,
    //                $meta->getTable()->name,
    //                false,
    //                false,
    //                null,
    //                null,
    //                $column->type,
    //                $column->length,
    //                $column->nullable,
    //                $column->increment,
    //                $column->precision,
    //                $column->scale,
    //                $column->unique === 1 ? true : null,
    //                $column->uqGroup,
    //                $column->index === 1 ? true : null,
    //                $column->idxGroup,
    //                $column->unsigned,
    //                $column->nullDefault,
    //                $column->default
    //            ));
    //        }
    //        return $columns;
    //    }
}
