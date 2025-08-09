<?php

namespace Subtext\Persistables\Databases\Meta;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;
use Subtext\Persistables\Collection as PersistableCollection;
use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Columns;
use Subtext\Persistables\Databases\Attributes\Join;
use Subtext\Persistables\Databases\Attributes\Joins;
use Subtext\Persistables\Databases\Attributes\Entity;
use Subtext\Persistables\Databases\Attributes\Entities;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Meta;
use Subtext\Persistables\Persistable;

class Factory
{
    public const array SQL_JOINS   = ['INNER','LEFT','RIGHT','FULL OUTER','JOIN'];
    private static ?self $instance = null;
    private Collection $meta;

    private function __construct()
    {
        $this->meta = new Collection();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $class): Meta
    {
        $this->validate($class);
        if (!$this->meta->has($class)) {
            $inspect = new ReflectionClass($class);
            $tables  = $inspect->getAttributes(Table::class);
            $table   = $tables[0]->newInstance();
            $joins   = new Joins\Collection(array_map(function ($attr) {
                return $attr->newInstance();
            }, $inspect->getAttributes(Join::class)));
            list($columns, $persistables) = array_reduce(
                $inspect->getProperties(),
                function ($carry, $property) {
                    $cols = $property->getAttributes(Column::class);
                    if (!empty($cols)) {
                        $carry[0]->set($property->name, $cols[0]->newInstance());
                    }
                    $nested = $property->getAttributes(Entity::class);
                    if (!empty($nested)) {
                        $child = $nested[0]->newInstance();
                        if (!empty($child->class)) {
                            $this->validate($child->class);
                            $targetClass = $child->class;
                        } else {
                            // infer from property type
                            $type  = $property->getType();
                            $targetClass = null;
                            if ($type instanceof ReflectionNamedType) {
                                $this->validate($type->getName());
                                $targetClass = $type->getName();
                            } elseif ($type instanceof ReflectionUnionType) {
                                foreach ($type->getTypes() as $innerType) {
                                    if (is_subclass_of($innerType->getName(), Persistable::class)) {
                                        $targetClass = $innerType->getName();
                                        break;
                                    }
                                }
                            }
                        }
                        if (empty($targetClass)) {
                            // @todo throw a configuration exception this should not happen
                        }
                        $carry[1]->set($property->name, new Entity($targetClass, $child->foreign));
                    }
                    return $carry;
                },
                [new Columns\Collection(), new Entities\Collection()]
            );
            if ($joins->isEmpty()) {
                $joins = null;
            }
            $this->meta->set($class, new Meta($table, $columns, $joins, $persistables));
        }

        return $this->meta->get($class);
    }

    private function validate(string $class): void
    {
        if (!class_exists($class) || !is_subclass_of($class, Persistable::class)) {
            throw new InvalidArgumentException(sprintf(
                'Class %s does not exist or does not implement Persistable',
                $class
            ));
        }
    }
}
