<?php

namespace Subtext\Persistables\Databases\Meta;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionProperty;
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
    private static ?self $instance = null;
    private Collection $meta;

    private function __construct()
    {
        $this->meta = new Collection();
    }

    public static function getInstance(bool $new = false): self
    {
        if (self::$instance === null || $new) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * If the Meta class for a given Persistable class does not exist in the
     * collection, then it is constructed and validated using reflection.
     *
     * @param string $class
     * @return Meta
     * @throws ReflectionException
     */
    public function get(string $class): Meta
    {
        $this->validateClass($class);
        if (!$this->meta->has($class)) {
            $inspect = new ReflectionClass($class);
            $tables  = $inspect->getAttributes(Table::class);
            if (empty($tables)) {
                throw new InvalidArgumentException(sprintf(
                    'Persistable class %s requires a Table attribute.',
                    $class
                ));
            }
            $table = $tables[0]->newInstance();
            $joins = new Joins\Collection(array_map(function ($attr) {
                return $attr->newInstance();
            }, $inspect->getAttributes(Join::class)));
            list($columns, $persistables) = array_reduce(
                $inspect->getProperties(),
                [$this, 'getAttributesForProperties'],
                [new Columns\Collection(), new Entities\Collection()]
            );
            if ($columns->isEmpty()) {
                throw new InvalidArgumentException(sprintf(
                    'Persistable class %s requires one or more Column attributes.',
                    $class
                ));
            }
            $this->meta->set($class, new Meta(
                $table,
                $columns,
                $joins->isEmpty() ? null : $joins,
                $persistables->isEmpty() ? null : $persistables
            ));
        }

        return $this->meta->get($class);
    }

    /**
     * Loop through reflection class properties and look for attributes. For
     * some attributes, if values are not explicitly set, they can be inferred
     * from other reflection data.
     *
     * @param array $carry                 An initial array, it should be
     *                                     populated with a column collection
     *                                     and an entity collection.
     * @param ReflectionProperty $property The property to inspect.
     *
     * @return array An array with two elements the columns collection and the
     *               entities collection.
     */
    private function getAttributesForProperties(array $carry, ReflectionProperty $property): array
    {
        $name    = $property->getName();
        $class   = $property->getDeclaringClass()->getName();
        $columns = $property->getAttributes(Column::class);
        if (!empty($columns)) {
            $instance = $columns[0]->newInstance();
            $carry[0]->set($property->getName(), new Column(
                $instance->name ?? $property->getName(),
                $instance->table,
                $instance->primary,
                $instance->readonly,
                $this->parseAccessor($instance, $name, $class),
                $this->parseAccessor($instance, $name, $class, 'set', boolval($instance->readonly))
            ));
        }
        $nested = $property->getAttributes(Entity::class);
        if (!empty($nested)) {
            $child = $nested[0]->newInstance();
            $type  = $property->getType();
            if (!empty($child->class)) {
                // class is implicitly set
                $targetClass = $child->class;
            } else {
                // infer from property type
                $targetClass = null;
                if ($type instanceof ReflectionNamedType) {
                    $this->validateClass($type->getName());
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
            $this->validateClass($targetClass ?? '');
            $carry[1]->set($property->name, new Entity(
                $targetClass,
                $child->foreign,
                $type->allowsNull(),
                is_subclass_of($targetClass, PersistableCollection::class),
                $this->parseAccessor($child, $name, $class),
                $this->parseAccessor($child, $name, $class, 'set'),
                $child->order
            ));
        }

        return $carry;
    }

    /**
     * Returns the validated string name of the getter or setter for the property
     * on the specified class.
     *
     * @param object $instance The attribute instance with a getter or setter.
     * @param string $name     The name of the property.
     * @param string $class    The fqdn name of the class.
     * @param string $type     An accessor prefix: 'get' or 'set'
     * @param bool $readonly   A readonly column has no setter
     *
     * @return string|null The validated name of a method used to access the property.
     */
    private function parseAccessor(
        object $instance,
        string $name,
        string $class,
        string $type = 'get',
        bool $readonly = false,
    ): ?string {
        if ($type === 'set' && $readonly) {
            $accessor = null;
        } else {
            $param = ($type === 'get' ? 'getter' : 'setter');
            if (is_null($instance->$param)) {
                $accessor = $this->inferAccessorName($name, $class, $type);
            } else {
                $accessor = $instance->$param;
                $this->validateMethod($class, $accessor);
            }
        }
        return $accessor;
    }

    private function inferAccessorName(string $property, string $class, string $type = 'get'): ?string
    {
        $method = $type . ucfirst($property);
        $this->validateMethod($class, $method);
        return $method;
    }

    private function validateMethod(string $class, string $method): void
    {
        if (!method_exists($class, $method)) {
            throw new InvalidArgumentException(sprintf(
                'Method %s not found in class %s',
                $method,
                $class
            ));
        }
    }

    private function validateClass(string $class): void
    {
        if (class_exists($class)) {
            if (is_subclass_of($class, Persistable::class) || is_subclass_of($class, PersistableCollection::class)) {
                return;
            }
        }
        throw new InvalidArgumentException(sprintf(
            'Class %s does not exist or does not implement Persistable',
            $class
        ));
    }
}
