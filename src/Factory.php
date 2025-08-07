<?php

namespace Subtext\Persistables;

use InvalidArgumentException;
use PDO;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Sql;

class Factory
{
    public const string ERROR_TYPE = 'The object must be an instance of Persistable or Collection';
    protected Sql $db;
    private Databases\Meta\Collection $meta;

    public function __construct(Sql $db)
    {
        $this->db = $db;
        $this->meta = new Databases\Meta\Collection();
    }

    public function getEntityByPrimaryKey(string $entity, mixed $primaryKeyValue): Persistable
    {
        if (!(class_exists($entity) && is_subclass_of($entity, Persistable::class))) {
            throw new InvalidArgumentException(
                'Entity must be a class which implements Persistable.'
            );
        }
        $query = Sql::getSelectQuery($this->getMeta($entity));
        return $this->db->getQueryRow($query, [$primaryKeyValue], PDO::FETCH_CLASS, $entity);
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
            unset($item);
        }
        if ($persistable instanceof Persistable) {
            foreach (($persistable->getPersistables() ?? []) as $item) {
                $this->persist($item);
            }
        } else {
            foreach ($persistable as $item) {
                foreach ($item->getPersistables() ?? [] as $child) {
                    $this->persist($child);
                }
            }
        }
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
     * Determines if the persistable object needs to be inserted
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
     * Determines if the persistable object needs to be updated
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
     * Query the database for a collection of entities with the given query and
     * params. The collection will determine the class of the objects returned.
     *
     * @param string $sql
     * @param Collection $collection
     * @param array $params
     *
     * @return Collection
     */
    protected function getEntityCollection(
        string $sql,
        Collection $collection,
        array $params
    ): Collection {
        foreach ($this->db->getQueryData(
            $sql,
            $params,
            PDO::FETCH_CLASS,
            $collection->getEntityClass()
        ) as $item) {
            if (!$collection->has($item->getId())) {
                $collection->set($item->getId(), $item);
            } else {
                $collection->append($item);
            }
        }
        return $collection;
    }

    /**
     * @param Persistable $entity
     *
     * @return void
     * @throws ReflectionException
     */
    protected function deleteEntity(Persistable $entity): void
    {
        $sql = Sql::getDeleteQuery($this->getTable($entity), $this->getPrimaryKey($entity));
        if (!$this->db->execute($sql, [$this->getPrimaryKeyValue($entity)])) {
            throw new RuntimeException(
                'The database records could not be deleted'
            );
        }
    }

    /**
     * @throws ReflectionException
     */
    protected function performInsertOperation(Persistable|Collection $object): void
    {
        $isCollection = ($object instanceof Collection);
        $isEntity     = ($object instanceof Persistable);
        if ($isCollection) {
            $params = [];
            foreach ($object as $item) {
                $params[] = $this->getDbParams($item, excludePrimaryKey: true);
            }
        } else {
            $params = $this->getDbParams($object, excludePrimaryKey: true);
        }
        if (($isCollection && $object->count() > 0 && count($params) > 0) || $isEntity) {
            $table = $this->getTable($object);
            if (!array_is_list($params)) {
                $params = [$params];
            }
            $current = current($params);
            $sql     = Sql::getInsertQuery($current, $table);
            $params  = $this->stripKeysFromParams($params);
            $query   = Sql::formatInsert($sql, $params);
            $lastId  = $this->db->getIdForInsert($query, $params);
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
                        $lastId++;
                    }
                    $item->resetModified();
                }
            }
        }
    }

    /**
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
     * Save the modified data from a persistable object to the database
     *
     * @param Persistable $object
     *
     * @return void
     * @throws ReflectionException
     */
    protected function performSingleUpdate(Persistable $object): void
    {
        $params = $this->getDbParams($object, true, true);
        if (!empty($params)) {
            $table        = $this->getTable($object);
            $key          = $this->getPrimaryKey($object);
            $itemSql      = Sql::getUpdateQuery($params, $table, $key);
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
     * @throws ReflectionException
     */
    protected function performDeleteOperation(Persistable|Collection $object): void
    {
        $params = [];
        if ($object instanceof Persistable) {
            $table   = $this->getTable($object);
            $primary = $this->getPrimaryKey($object);
            foreach (($object->getPersistables() ?? []) as $childObject) {
                $this->performDeleteOperation($childObject);
            }
            if (!is_null($id = $this->getPrimaryKeyValue($object))) {
                array_push($params, $id);
            }
        } else {
            $table   = $this->getTable($object->getFirst());
            $primary = $this->getPrimaryKey($object->getFirst());
            foreach ($object as $item) {
                if (!is_null($id = $item->getId())) {
                    array_push($params, $id);
                }
            }
        }
        $query = Sql::getDeleteQuery($table, $primary);
        if (count($params)) {
            if (!$this->db->execute(Sql::formatIn($query, count($params)), $params)) {
                throw new RuntimeException(
                    'The database records could not be deleted'
                );
            }
        }
    }

    protected function stripKeysFromParams(array $params): array
    {
        $output = [];
        foreach ($params as $data) {
            if (is_array($data)) {
                $output[] = array_values($data);
            }
        }
        return $output;
    }

    /**
     * @throws ReflectionException
     */
    protected function getDbParams(
        Persistable $object,
        bool $modified = false,
        bool $excludePrimaryKey = false
    ): array
    {
        $data  = [];
        $meta = $this->getMeta($object::class);
        $cols = $meta->getColumns();
        if ($modified) {
            foreach ($object->getModified()->getNames() as $property) {
                if ($cols->has($property)) {
                    $column = $cols->get($property);
                    if ($excludePrimaryKey && $column->primary) {
                        continue;
                    }
                    $method = $this->accessorName($object, $property);
                    $data[$column->name] = $object->$method();
                }
            }
        } else {
            foreach ($cols as $property => $column) {
                if ($excludePrimaryKey && $column->primary) {
                    continue;
                }
                $method = $this->accessorName($object, $property);
                $data[$column->name] = $object->$method();
            }
        }

        return $data;
    }

    /**
     * @throws ReflectionException
     */
    protected function getTable(Persistable $object): string
    {
        return $this->getMeta($object::class)->getTable()->name;
    }

    /**
     * @throws ReflectionException
     */
    protected function getPrimaryKey(Persistable $object): string
    {
        return $this->getMeta($object::class)->getTable()->primaryKey;
    }

    /**
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
     * @throws ReflectionException
     */
    private function getMeta(string $class): Databases\Meta
    {
        if (!$this->meta->has($class)) {
            $inspect = new ReflectionClass($class);
            $attr = $inspect->getAttributes(Table::class);
            $table = $attr[0]->newInstance();
            $columns = new Databases\Attributes\Columns\Collection();
            foreach ($inspect->getProperties() as $property) {
                foreach($property->getAttributes(Column::class) as $attribute) {
                    $columns->set($property->getName(), $attribute->newInstance());
                }
            }
            $this->meta->set($class, new Databases\Meta($table, $columns));
        }
        return $this->meta->get($class);
    }

    /**
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
