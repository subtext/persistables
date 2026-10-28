<?php

namespace Subtext\Persistables\Databases\Meta;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Subtext\Persistables\Collection as PersistableCollection;
use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Columns;
use Subtext\Persistables\Databases\Attributes\Columns\Type as SqlLogicalType;
use Subtext\Persistables\Databases\Attributes\Join;
use Subtext\Persistables\Databases\Attributes\Joins;
use Subtext\Persistables\Databases\Attributes\Entity;
use Subtext\Persistables\Databases\Attributes\Entities;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Meta;
use Subtext\Persistables\Exceptions\AmbiguousTypeInterfaceException;
use Subtext\Persistables\Exceptions\InvalidAccessorException;
use Subtext\Persistables\Exceptions\InvalidPropertyTypeException;
use Subtext\Persistables\Exceptions\MissingAttributeException;
use Subtext\Persistables\Exceptions\SchemaDefinitionException;
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
     * @throws InvalidArgumentException If the class is missing or is not a Persistable or PersistableCollection subclass.
     * @throws ReflectionException
     * @throws SchemaDefinitionException
     */
    public function get(string $class): Meta
    {
        $this->validateClass($class);
        if (!$this->meta->has($class)) {
            $inspect = new ReflectionClass($class);
            $tables  = $inspect->getAttributes(Table::class);
            if (empty($tables)) {
                throw new MissingAttributeException(sprintf(
                    'Persistable class %s requires a Table attribute.',
                    $class
                ));
            }
            $joins = new Joins\Collection(array_map(function ($attr) {
                return $attr->newInstance();
            }, $inspect->getAttributes(Join::class)));
            $meta = new Meta(
                $tables[0]->newInstance(),
                new Columns\Collection(),
                $joins,
                new Entities\Collection()
            );
            foreach ($inspect->getProperties() as $property) {
                $this->getAttributesForProperties($meta, $property);
            }
            if ($meta->getColumns()->isEmpty()) {
                throw new MissingAttributeException(sprintf(
                    'Persistable class %s requires one or more Column attributes.',
                    $class
                ));
            }
            $this->meta->set($class, new Meta(
                $meta->getTable(),
                $meta->getColumns(),
                $meta->getJoins()->isEmpty() ? null : $meta->getJoins(),
                $meta->getPersistables()->isEmpty() ? null : $meta->getPersistables()
            ));
        }

        return $this->meta->get($class);
    }

    /**
     * Retrieves the collection of metadata associated with the current context.
     *
     * @return Collection The collection containing metadata items.
     */
    public function getMeta(): Collection
    {
        return $this->meta;
    }

    /**
     * Parses and collects the attributes associated with the given property of
     * a Persistable class and updates the Meta object.
     *
     * @param Meta $meta The metadata object where processed attributes will be stored.
     * @param ReflectionProperty $property The reflected property whose attributes are to be processed.
     * @return void
     * @throws InvalidArgumentException If a nested entity class is invalid.
     * @throws SchemaDefinitionException
     */
    private function getAttributesForProperties(Meta $meta, ReflectionProperty $property): void
    {
        $name    = $property->getName();
        $class   = $property->getDeclaringClass()->getName();
        $columns = $property->getAttributes(Column::class);
        /** @var Column $first */
        $first   = $columns[0]->newInstance();
        if (!empty($columns)) {
            $meta->getColumns()->set(
                $property->getName(),
                $this->getColumnWithInferredValues(
                    $property,
                    $meta->getTable(),
                    $first
                )
            );
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
            $meta->getPersistables()->set($property->name, new Entity(
                $targetClass,
                $child->foreign,
                $type->allowsNull(),
                is_subclass_of($targetClass, PersistableCollection::class),
                $this->parseAccessor($child, $name, $class),
                $this->parseAccessor($child, $name, $class, 'set'),
                $child->order
            ));
        }
    }

    /**
     * Returns the validated string name of the getter or setter for the property
     * on the specified class.
     *
     * @param object $instance The attribute instance with a getter or setter.
     * @param string $name The name of the property.
     * @param string $class The fqdn name of the class.
     * @param string $type An accessor prefix: 'get' or 'set'
     * @param bool $readonly A readonly column has no setter
     *
     * @return string|null The validated name of a method used to access the property.
     * @throws InvalidAccessorException
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

    /**
     * Infers the accessor method name based on the provided property, class,
     * and accessor type.
     *
     * @param string $property The name of the property for which the accessor
     *                         method is being inferred.
     * @param string $class    The name of the class containing the property.
     * @param string $type     The accessor prefix type (e.g., 'get', 'set').
     *                         Defaults to 'get'.
     *
     * @return string The inferred and validated accessor method name.
     *
     * @throws InvalidAccessorException If the constructed method name is
     *                                  invalid or missing in the class.
     */
    private function inferAccessorName(
        string $property,
        string $class,
        string $type = 'get'
    ): ?string {
        $method = $type . ucfirst($property);
        $this->validateMethod($class, $method);
        return $method;
    }


    /**
     * Validates the existence of a method within a given class.
     *
     * @param string $class The fully qualified name of the class to be checked.
     * @param string $method The name of the method to validate within the class.
     *
     * @return void
     *
     * @throws InvalidAccessorException If the specified method does not exist in
     *                                  the provided class.
     */
    private function validateMethod(string $class, string $method): void
    {
        if (!method_exists($class, $method)) {
            throw InvalidAccessorException::create($class, $method);
        }
    }

    /**
     * Validates if the provided class name exists and implements the required
     * Persistable or PersistableCollection base class.
     *
     * @param string $class The fully qualified class name to validate.
     *
     * @return void
     *
     * @throws InvalidArgumentException If the class does not exist or does not
     *                                  extend either required base class.
     */
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

    /**
     * Infers the database column definition based on the provided property and
     * instance configuration.
     *
     * @param ReflectionProperty $property The reflection of the property being
     *                                     processed to infer the column definition.
     * @param Table $table                 The table to which the column belongs.
     * @param Column $instance             The column instance containing pre-
     *                                     configured values and metadata.
     *
     * @return Column The fully resolved column instance with inferred values
     *                for type, length, precision, scale, and constraints.
     *
     * @throws InvalidPropertyTypeException If the property type is invalid or unhandled.
     * @throws AmbiguousTypeInterfaceException If the property type cannot be uniquely inferred.
     * @throws InvalidAccessorException If the accessor method is invalid or missing.
     */
    private function getColumnWithInferredValues(
        ReflectionProperty $property,
        Table $table, Column $instance
    ): Column {
        $sqlType = $instance->type;
        if (!$sqlType instanceof SqlLogicalType) {
            $types = $property->getType();
            if ($types === null) {
                throw InvalidPropertyTypeException::create($property);
            }
            if ($types instanceof ReflectionNamedType) {
                $type = $types->getName();
                $sqlType = $this->phpToSqlType($type, $instance);
            } elseif ($types instanceof ReflectionUnionType) {
                foreach ($types->getTypes() as $namedType) {
                    if ($sqlType = $this->phpToSqlType($namedType->getName(), $instance)) {
                        break;
                    }
                }
            } elseif ($types instanceof ReflectionIntersectionType) {
                throw InvalidPropertyTypeException::create($property);
            }
            if ($sqlType === null) {
                throw AmbiguousTypeInterfaceException::create($property);
            }
        }

        $length    = null;
        $precision = null;
        $scale     = null;
        switch ($sqlType) {
            case SqlLogicalType::CHARACTER:
            case SqlLogicalType::STRING:
                $length = ($instance->limit ?? 255);
                break;
            case SqlLogicalType::DECIMAL:
                $scale = $instance->scale ?? 9;
                $precision = $instance->precision ?? 2;
                break;
            default:
                // length, precision, and scale are not needed
        }

        $name  = $property->getName();
        $class = $property->getDeclaringClass()->getName();
        return new Column(
            $instance->name ?? $name,
            $instance->table ?? $table->name,
            $instance->primary,
            $instance->readonly,
            $instance->getter ?? $this->inferAccessorName($name, $class),
            $instance->setter ?? ($instance->readonly
                ? null
                : $this->inferAccessorName($name, $class, 'set')),
            $instance->type ?? $sqlType,
            $instance->limit ?? $length,
            $instance->nullable ?? $property->getType()->allowsNull(),
            $instance->increment ?? false,
            $instance->precision ?? $precision,
            $instance->scale ?? $scale,
            $instance->unique ?? false,
            $instance->uqGroup,
            $instance->index ?? false,
            $instance->idxGroup,
            $instance->unsigned ?? false,
            $instance->nullDefault,
            $instance->default,
        );
    }

    /**
     * Converts a PHP type to a corresponding SQL logical type based on the provided
     * column metadata and PHP type.
     *
     * @param string $php The PHP type as a string, such as 'int', 'string', or a class name.
     * @param Column $column The column instance with metadata such as data length or limits.
     *
     * @return SqlLogicalType|null The inferred SQL logical type, or null if it cannot be determined.
     */
    private function phpToSqlType(String $php, Column $column): ?SqlLogicalType
    {
        switch ($php) {
            case 'int':
                $sql = SqlLogicalType::tryFrom('integer');
                break;
            case 'string':
                if ($column->limit === null) {
                    $sql = SqlLogicalType::tryFrom('text');
                } elseif ($column->limit >= 256) {
                    $sql = SqlLogicalType::tryFrom('text');
                } else {
                    $sql = SqlLogicalType::tryFrom('varchar');
                }
                break;
            case 'DateTime':
            case 'DateTimeImmutable':
            case 'DateTimeInterface':
                $sql = SqlLogicalType::tryFrom('datetime');
                break;
            case 'array':
            case 'object':
            case 'stdClass':
                $sql = SqlLogicalType::tryFrom('json');
                break;
            default:
                $sql = SqlLogicalType::tryFrom($php);
        }

        return $sql;
    }}
