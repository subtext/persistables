<?php

namespace Subtext\Persistables;

use InvalidArgumentException;
use PDO;
use ReflectionException;
use RuntimeException;
use Subtext\Persistables\Databases\Attributes\Entities;
use Subtext\Persistables\Databases\Attributes\Entity;
use Subtext\Persistables\Databases\Attributes\PersistOrder;
use Subtext\Persistables\Databases\Meta;
use Subtext\Persistables\Databases\Sql;

class Factory
{
    protected Sql $db;
    private Meta\Factory $meta;

    public function __construct(Sql $db)
    {
        $this->db   = $db;
        $this->meta = Meta\Factory::getInstance();
    }

    /**
     * Retrieve a single entity from the database using its primary key.
     *
     * @param string $entity
     * @param mixed $primaryKeyValue
     *
     * @return Persistable|null
     * @throws ReflectionException
     */
    public function getEntityByPrimaryKey(string $entity, mixed $primaryKeyValue): ?Persistable
    {
        if (!(class_exists($entity) && is_subclass_of($entity, Persistable::class))) {
            throw new InvalidArgumentException(
                'Entity must be a class which implements Persistable.'
            );
        }
        $meta   = $this->getMeta($entity);
        $result = $this->db->getQueryRow(
            $this->db->getSelectQuery($meta),
            [$primaryKeyValue],
            PDO::FETCH_CLASS,
            $entity
        );
        if ($result) {
            $this->recursivelyHydrate($result, $meta);
        }
        return empty($result) ? null : $result;
    }

    /**
     * Query the database for a collection of entities with the given query and
     * params. The collection will determine the class of the objects returned.
     *
     * @param string $sql
     * @param Collection $collection
     * @param array $params
     * @param bool $append
     *
     * @return Collection
     * @throws ReflectionException
     */
    public function getEntityCollection(
        string $sql,
        Collection $collection,
        array $params,
        bool $append = true
    ): Collection {
        foreach ($this->db->getQueryData(
            $sql,
            $params,
            PDO::FETCH_CLASS,
            $collection->getEntityClass()
        ) as $entity) {
            if ($append) {
                $collection->append($entity);
            } else {
                $collection->set($this->getPrimaryKeyValue($entity), $entity);
            }
        }
        return $collection;
    }

    /**
     * Saves an object's state to the database by generating sql queries across
     * all tables used in populating the object data. This is a recursive method
     * which uses the getPersistables method of the Persistable class to
     * recursively apply the database logic to other embedded objects.
     *
     * @param Persistable|Collection $persistable
     *
     * @return void
     * @throws ReflectionException
     */
    public function persist(Persistable|Collection $persistable): void
    {
        $this->recursivelyHandlePersistables($persistable, PersistOrder::BEFORE);

        if ($this->isDbInsert($persistable)) {
            $this->performInsertOperation($persistable);
        } elseif ($this->isDbUpdate($persistable)) {
            $this->performUpdateOperation($persistable);
        } elseif ($persistable instanceof Collection) {
            foreach ($persistable as $item) {
                if ($this->isDbInsert($item)) {
                    $this->performInsertOperation($item);
                } elseif ($this->isDbUpdate($item)) {
                    $this->performSingleUpdate($item);
                }
            }
        }
        $this->recursivelyHandlePersistables($persistable, PersistOrder::AFTER);
    }

    /**
     * Delete an entity from the database
     *
     * @param Persistable|Collection $persistable
     *
     * @return void
     * @throws ReflectionException
     */
    public function desist(Persistable|Collection $persistable): void
    {
        $this->performDeleteOperation($persistable);
    }

    /**
     * Determines if the persistable object needs to be inserted.
     *
     * @param Persistable|Collection $persistable
     *
     * @return bool
     * @throws ReflectionException
     */
    protected function isDbInsert(Persistable|Collection $persistable): bool
    {
        if ($persistable instanceof Collection) {
            $isInsert = $persistable->reduce([$this, 'isPersistableInsert'], false);
        } else {
            $isInsert = $this->isPersistableInsert($persistable);
        }
        return $isInsert;
    }

    /**
     * Determines if the persistable object needs to be updated.
     *
     * @param Persistable|Collection $persistable
     *
     * @return bool
     * @throws ReflectionException
     */
    protected function isDbUpdate(Persistable|Collection $persistable): bool
    {
        if ($persistable instanceof Collection) {
            $isUpdate = $persistable->reduce([$this, 'isPersistableUpdate'], false);
        } else {
            $isUpdate = $this->isPersistableUpdate($persistable);
        }
        return $isUpdate;
    }

    /**
     * Insert a single entity, or a collection of entities, into the database.
     *
     * @param Persistable|Collection $object
     *
     * @return void
     * @throws ReflectionException
     */
    protected function performInsertOperation(Persistable|Collection $object): void
    {
        $isCollection = ($object instanceof Collection);
        $isEntity     = ($object instanceof Persistable);
        if ($isCollection) {
            $meta   = $this->getMeta($object->getEntityClass());
            $params = [];
            foreach ($object as $item) {
                $params[] = $this->getDbParams($item, $meta, excludePrimaryKey: true);
            }
        } else {
            $meta   = $this->getMeta($object::class);
            $params = $this->getDbParams($object, $meta, excludePrimaryKey: true);
        }
        if (($isCollection && $object->count() > 0 && count($params) > 0) || $isEntity) {
            if (!array_is_list($params)) {
                $params = [$params];
            }
            $sql    = $this->db->getInsertQuery($meta, count($params));
            $params = $this->getParameterValues($params);
            $lastId = $this->db->getIdForInsert($sql, $params);
            if ($lastId === 0) {
                throw new RuntimeException(
                    'The database records could not be inserted'
                );
            } elseif ($isEntity) {
                $this->setPrimaryKeyValue($object, $lastId);
                $object->resetModified();
            } elseif ($isCollection) {
                foreach ($object as $item) {
                    if ($this->isDbInsert($item)) {
                        $this->setPrimaryKeyValue($item, $lastId);
                        $item->resetModified();
                        $lastId++;
                    }
                    $item->resetModified();
                }
            }
        }
    }

    /**
     * Update a single entity, or a collection of entities, in the database. Uses
     * the modifications collection to determine the values to be updated.
     *
     * @param Persistable|Collection $object
     *
     * @return void
     * @throws ReflectionException
     */
    protected function performUpdateOperation(Persistable|Collection $object): void
    {
        if ($object instanceof Persistable) {
            $this->performSingleUpdate($object);
        } else {
            foreach ($object as $item) {
                $this->performSingleUpdate($item);
            }
        }
    }

    /**
     * Save the modified data from a persistable object to the database.
     *
     * @param Persistable $object
     *
     * @return void
     * @throws ReflectionException
     */
    protected function performSingleUpdate(Persistable $object): void
    {
        $params = $this->getDbParams(
            $object,
            $this->getMeta($object::class),
            modified: true,
            excludePrimaryKey: true
        );
        if (!empty($params)) {
            $itemSql = $this->db->getUpdateQuery(
                $this->getMeta($object::class),
                $object->getModified()
            );
            $itemParams   = array_values($params);
            $itemParams[] = $this->getPrimaryKeyValue($object);
            if (!$this->db->execute($itemSql, $itemParams)) {
                throw new RuntimeException(
                    'The database records could not be updated'
                );
            }
        }
    }

    /**
     * Delete an entity, or collection of entities from the database.
     *
     * @param Persistable|Collection $object
     *
     * @return void
     * @throws ReflectionException
     */
    protected function performDeleteOperation(Persistable|Collection $object): void
    {
        $params = [];
        // entities owned by this entity will also be deleted recursively
        $this->recursivelyHandlePersistables($object, PersistOrder::AFTER, false);
        if ($object instanceof Persistable) {
            $meta = $this->getMeta($object::class);
            $rows = 1;
            if (!is_null($id = $this->getPrimaryKeyValue($object))) {
                $params[] = $id;
            }
        } else {
            $meta = $this->getMeta($object->getEntityClass());
            $rows = $object->count();
            foreach ($object as $item) {
                if (!is_null($id = $this->getPrimaryKeyValue($item))) {
                    $params[] = $id;
                }
            }
        }
        $query = $this->db->getDeleteQuery($meta, $rows);
        if (count($params)) {
            if (!$this->db->execute($query, $params)) {
                throw new RuntimeException(
                    'The database records could not be deleted'
                );
            }
        }
    }

    /**
     * A utility function for insert data, it strips keys and combines nested arrays
     * into a single array used with a PDOStatement to bind query values.
     *
     * @param array $params
     *
     * @return array A flattened sequential array
     */
    protected function getParameterValues(array $params): array
    {
        $output = [];
        foreach ($params as $data) {
            if (is_array($data)) {
                $values = array_values($data);
                array_push($output, ...$values);
            }
        }
        return $output;
    }

    /**
     * Creates a map of database column names and their associated values, to be
     * used in generating SQL statements.
     *
     * @param Persistable $object
     * @param Meta $meta
     * @param bool $modified
     * @param bool $excludePrimaryKey
     *
     * @return array
     * @throws ReflectionException
     */
    protected function getDbParams(
        Persistable $object,
        Meta $meta,
        bool $modified = false,
        bool $excludePrimaryKey = false
    ): array {
        $data = [];
        $cols = $meta->getColumns();
        if ($modified) {
            foreach ($object->getModified()->getNames() as $property) {
                if ($cols->has($property)) {
                    $column = $cols->get($property);
                    if ($excludePrimaryKey && $column->primary) {
                        continue;
                    }
                    $method              = $this->accessorName($object, $property);
                    $data[$column->name] = $object->$method();
                }
            }
        } else {
            foreach ($cols as $property => $column) {
                if (($excludePrimaryKey && $column->primary) || $column->readonly) {
                    continue;
                }
                $method              = $this->accessorName($object, $property);
                $data[$column->name] = $object->$method();
            }
        }

        return $data;
    }

    /**
     * Get the column name of the primary key for the table.
     *
     * @param Persistable $object
     *
     * @return string
     * @throws ReflectionException
     */
    protected function getPrimaryKey(Persistable $object): string
    {
        return $this->getMeta($object::class)->getTable()->primaryKey;
    }

    /**
     * Get the value of the primary key for the table. This could be anything,
     * * a number, a string, or null.
     *
     * @param Persistable $object
     *
     * @return mixed
     * @throws ReflectionException
     * @throws RuntimeException
     */
    protected function getPrimaryKeyValue(Persistable $object): mixed
    {
        $method = $this->accessorName($object, $this->getPrimaryKey($object));
        return $object->$method();
    }

    /**
     * @throws ReflectionException
     */

    /**
     * Determines the getter method for the primary key of the object, and sets
     * the value returned by the database.
     *
     * @param Persistable $object
     * @param mixed $value
     *
     * @return void
     * @throws ReflectionException
     * @throws RuntimeException
     */
    protected function setPrimaryKeyValue(Persistable $object, mixed $value): void
    {
        $method = $this->accessorName(
            $object,
            $this->getPrimaryKey($object),
            'set'
        );
        $object->$method($value);
    }

    /**
     * Uses reflection to parse metadata from a FQDN for a class which extends
     * Persistable. Table and Column attribute data is cached for each class.
     *
     * @param string $class
     *
     * @return Meta
     * @throws ReflectionException
     */
    private function getMeta(string $class): Meta
    {
        return $this->meta->get($class);
    }

    /**
     * Determine if the object instance should be inserted into the database.
     *
     * @param Persistable $object The persistable instance object to evaluate
     * @param bool $carry         Used with a collection and the reduce method
     *
     * @return bool
     * @throws ReflectionException
     */
    private function isPersistableInsert(Persistable $object, bool $carry = true): bool
    {
        if ($carry) {
            $carry = is_null($this->getPrimaryKeyValue($object));
        }
        return $carry;
    }

    /**
     * Determine if the object instance has been modified, and needs to be
     * updated in the database.
     *
     * @param Persistable $object The persistable instance object to evaluate
     * @param bool $carry         Used with a collection and the reduce method
     *
     * @return bool
     * @throws ReflectionException
     */
    private function isPersistableUpdate(Persistable $object, bool $carry = true): bool
    {
        if ($carry) {
            if (!is_null($this->getPrimaryKeyValue($object))) {
                $carry = $object->getModified()->count() > 0;
            }
        }
        return $carry;
    }

    /**
     * @param Persistable $object
     * @param Meta $meta
     * @param PersistOrder $order
     * @return Collection|null
     */
    private function getPersistables(Persistable $object, Meta $meta, PersistOrder $order): ?Collection
    {

        $entities  = ($meta->getPersistables() ?? new Entities\Collection());
        $childMeta = $entities->filter(function (Entity $entity) use ($order) {
            return $entity->order === $order;
        });
        $descendants = [];
        foreach ($childMeta as $property => $entity) {
            $getter = $entity->getter;
            $child  = $object->$getter();
            if ($child instanceof Collection) {
                if (!$child->isEmpty()) {
                    $descendants[$property] = $child;
                }
            } elseif (!is_null($child)) {
                $descendants[$property] = $child;
            }
        }
        $collection = null;
        if (count($descendants)) {
            $collection = new class ([]) extends Collection {
                public function getEntityClass(): string
                {
                    return Collection::class;
                }

                protected function validate(mixed $value): void
                {
                    if (!($value instanceof Collection || $value instanceof Persistable)) {
                        throw new InvalidArgumentException(sprintf(
                            'Value must be an instance of %s or %s',
                            Collection::class,
                            Persistable::class
                        ));
                    }
                }
            };
            foreach ($descendants as $property => $descendant) {
                $collection->set($property, $descendant);
            }
        }

        return $collection;
    }

    /**
     * @param Persistable $persistable
     * @param Meta $meta
     * @return void
     * @throws ReflectionException
     */
    private function recursivelyHydrate(Persistable $persistable, Meta $meta): void
    {
        foreach ($meta->getPersistables() ?? [] as $entity) {
            $childMeta = $this->getMeta($entity->class);
            if ($entity->order === PersistOrder::BEFORE) {
                $primaryKey = $entity->foreign ?? $childMeta->getTable()->primaryKey;
                $getter     = $this->accessorName($persistable, $primaryKey);
                $child      = $this->getEntityByPrimaryKey(
                    $entity->class,
                    $persistable->$getter()
                );
                $setter = $entity->setter;
            } else {
                $primaryKey = $this->getPrimaryKey($persistable);
                $clause     = sprintf('`%s` = ?', $entity->foreign ?? $primaryKey);
                $query      = $this->db->getSelectQuery($childMeta, $clause);
                $setter     = $entity->setter;
                if ($entity->collection) {
                    $child = new ($entity->class)();
                    $this->getEntityCollection($query, $child, []);
                } else {
                    $child = $this->db->getQueryRow(
                        $query,
                        [$this->getPrimaryKeyValue($persistable)],
                        PDO::FETCH_CLASS,
                        $entity->class
                    );
                }
            }
            $persistable->$setter($child);
        }
    }

    /**
     * Each persistable may contain child entities, which themselves can contain
     * persistable objects. Recursively loop through those children, performing
     * the chosen action on each.
     *
     * @param Persistable|Collection $persistable A persistable object or collection
     *                                            of persistable objects.
     * @param PersistOrder $order                 Defines the relationship between
     *                                            this entity and the child.
     * @param bool $persist                       If true, the persist action is
     *                                            used, otherwise desist.
     *
     * @return void
     * @throws ReflectionException
     */
    private function recursivelyHandlePersistables(
        Persistable|Collection $persistable,
        PersistOrder $order,
        bool $persist = true
    ): void {
        $isCollection = $persistable instanceof Collection;
        $meta         = $isCollection
              ? $this->getMeta($persistable->getEntityClass())
              : $this->getMeta($persistable::class);
        if ($isCollection) {
            foreach ($persistable as $item) {
                $this->recurse(
                    $this->getPersistables($item, $meta, $order) ?? [],
                    $persist
                );
            }
        } else {
            $children = $this->getPersistables($persistable, $meta, $order) ?? [];
            $this->recurse($children, $persist);
            if ($order === PersistOrder::BEFORE) {
                // apply children to parent object
                $properties = $meta->getPersistables();
                foreach ($children as $property => $child) {
                    $setter = $properties->get($property)->setter;
                    // allows the parent to update
                    $persistable->$setter($child);
                }
            }
        }
    }

    /**
     * Apply the persist or desist method to each object recursively.
     *
     * @param iterable $collection Could be a collection or an empty array
     * @param bool $persist        Persist when true, otherwise desist
     * @param ?Persistable $parent A parent object on which to apply updates.
     *
     * @return void
     * @throws ReflectionException
     */
    private function recurse(iterable $collection, bool $persist): void
    {
        foreach ($collection as $item) {
            if ($persist) {
                $this->persist($item);
            } else {
                $this->desist($item);
            }
        }
    }

    /**
     * Determine the getter or setter name for a given property using best
     * practices naming convention.
     *
     * @param Persistable $object
     * @param string $property
     * @param string $type
     *
     * @return string
     */
    private function accessorName(
        Persistable $object,
        string $property,
        string $type = 'get'
    ): string {
        $method = sprintf($type . '%s', ucfirst($property));
        if (!method_exists($object, $method)) {
            throw new RuntimeException(sprintf(
                'Persistable class "%s" does not have method "%s"',
                get_class($object),
                $method
            ));
        }
        return $method;
    }
}
