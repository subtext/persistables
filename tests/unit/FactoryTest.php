<?php

namespace Subtext\Persistables\Tests\Unit;

use ArrayIterator;
use DateTime;
use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Subtext\Collections\Text;
use Subtext\Persistables\Collection;
use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Columns;
use Subtext\Persistables\Databases\Attributes\Entity;
use Subtext\Persistables\Databases\Attributes\Entities;
use Subtext\Persistables\Databases\Attributes\PersistOrder;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Meta;
use Subtext\Persistables\Databases\Meta\Factory as MetaFactory;
use Subtext\Persistables\Databases\Sql;
use Subtext\Persistables\Factory;
use Subtext\Persistables\Modifications;
use Subtext\Persistables\Tests\Unit\Fixtures\SimpleEntity;
use InvalidArgumentException;
use Subtext\Persistables\Tests\Unit\Fixtures\WithEntityExplicit;

class FactoryTest extends TestCase
{
    public function testGetEntityByPrimaryKey(): void
    {
        $expected = new SimpleEntity();
        $expected->setId(1);
        $expected->setName('Omega');
        $expected->resetModified();

        $sql = <<<SQL
        SELECT `simple_entities`.`id`,
               `simple_entities`.`name`
        FROM `simple_entities`
        WHERE `simple_entities`.`id` = ?
        SQL;
        $meta = $this->createMock(Meta::class);

        $database = $this->createMock(Sql::class);
        $database->expects($this->once())
            ->method('getSelectQuery')
            ->with($this->equalTo($meta))
            ->willReturn($sql);
        $database->expects($this->once())
            ->method('getQueryRow')
            ->with(
                $this->equalTo($sql),
                $this->equalTo([1]),
                $this->equalTo(PDO::FETCH_CLASS),
                $this->equalTo(SimpleEntity::class)
            )->willReturn($expected);

        $metaFactory = $this->createMock(MetaFactory::class);
        $metaFactory->expects($this->once())
            ->method('get')
            ->willReturn($meta);

        $unit   = new Factory($database, $metaFactory);
        $actual = $unit->getEntityByPrimaryKey(SimpleEntity::class, 1);

        $this->assertEquals($expected, $actual);
    }

    public function testGetEntityByPrimaryKeyWithAggregate(): void
    {
        $sql = <<<SQL
        SELECT `with_entity_explicit`.`id`,
               `with_entity_explicit`.`simple_entity_id`
        FROM `with_entity_explicit`
        WHERE `with_entity_explicit`.`id` = ?
        SQL;

        $childSql = <<<SQL
        SELECT `simple_entities`.`id`,
               `simple_entities`.`name`
        FROM `simple_entities`
        WHERE `simple_entities`.`id` = ?
        SQL;

        $childEntity = $this->createMock(SimpleEntity::class);

        $entity = $this->createMock(WithEntityExplicit::class);
        $entity->expects($this->once())
            ->method('getEntityId')
            ->willReturn(25);
        $entity->expects($this->once())
            ->method('setChild')
            ->with($this->equalTo($childEntity));

        $meta = $this->createMock(Meta::class);
        $meta->expects($this->any())
            ->method('getTable')
            ->willReturn(new Table('with_entity_explicit', 'id'));
        $meta->expects($this->any())
            ->method('getColumns')
            ->willReturn(new Columns\Collection([
                'id' => new Column(),
            ]));
        $meta->expects($this->any())
            ->method('getPersistables')
            ->willReturn(new Entities\Collection([
                new Entity(
                    SimpleEntity::class,
                    'entityId',
                    getter: 'getChild',
                    setter: 'setChild',
                    order: PersistOrder::BEFORE
                ),
            ]));

        $child = $this->getMetaDataMock();

        $database = $this->createMock(Sql::class);
        $database->expects($this->exactly(2))
            ->method('getSelectQuery')
            ->willReturnCallback(function ($class, $clause) use ($sql, $childSql) {
                if ($class === SimpleEntity::class) {
                    $this->assertEquals('`simple_entities`.`id` = ?', $clause);
                    return $childSql;
                }
                $this->assertEquals(null, $clause);
                return $sql;
            });
        $database->expects($this->exactly(2))
            ->method('getQueryRow')
            ->willReturnCallback(function ($query, $params, $type, $class) use ($childEntity, $entity) {
                if ($class === SimpleEntity::class) {
                    return $childEntity;
                }
                return $entity;
            });

        $metaFactory = $this->createMock(MetaFactory::class);
        $metaFactory->expects($this->any())
            ->method('get')
            ->willReturnCallback(function (string $name) use ($meta, $child) {
                if ($name === SimpleEntity::class) {
                    return $child;
                }
                return $meta;
            });

        $unit = new Factory($database, $metaFactory);

        $actual = $unit->getEntityByPrimaryKey(WithEntityExplicit::class, 1);

        $this->assertInstanceOf(WithEntityExplicit::class, $actual);
        $this->assertInstanceOf(SimpleEntity::class, $actual->getChild());
    }

    public function testGetEntityByPrimaryKeyWillThrowInvalidArgumentException(): void
    {
        $database    = $this->createMock(Sql::class);
        $metaFactory = $this->createMock(MetaFactory::class);

        $unit = new Factory($database, $metaFactory);

        $this->expectException(InvalidArgumentException::class);
        $unit->getEntityByPrimaryKey('Subtext\\Persistables\\Foobar', 1);
    }

    public function testGetEntityCollectionWillAppendEntitiesByDefault(): void
    {
        $sql = <<<SQL
        SELECT `simple_entities`.`id`,
               `simple_entities`.`name`
        FROM `simple_entities`
        WHERE `simple_entities`.`id` IN (5,7,9)
        SQL;
        $expected = [new SimpleEntity(), new SimpleEntity(), new SimpleEntity()];

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('getEntityClass')
            ->willReturn(SimpleEntity::class);

        $database = $this->createMock(Sql::class);
        $database->expects($this->once())
            ->method('getQueryData')
            ->with(
                $this->equalTo($sql),
                $this->equalTo([]),
                $this->equalTo(PDO::FETCH_CLASS),
                $this->equalTo(SimpleEntity::class)
            )->willReturn($expected);

        $metaFactory = $this->createMock(MetaFactory::class);

        $unit   = new Factory($database, $metaFactory);
        $actual = $unit->getEntityCollection($sql, $collection, []);

        $this->assertSame($collection, $actual);

    }

    public function testGetEntityCollectionWillSetByEntityPrimaryKey(): void
    {
        $entity1 = $this->createMock(SimpleEntity::class);
        $entity1->expects($this->once())
            ->method('getId')
            ->willReturn(5);
        $entity2 = $this->createMock(SimpleEntity::class);
        $entity2->expects($this->once())
            ->method('getId')
            ->willReturn(7);
        $entity3 = $this->createMock(SimpleEntity::class);
        $entity3->expects($this->once())
            ->method('getId')
            ->willReturn(9);

        $expected = [$entity1, $entity2, $entity3];

        $sql = <<<SQL
        SELECT `simple_entities`.`id`,
               `simple_entities`.`name`
        FROM `simple_entities`
        WHERE `simple_entities`.`id` IN (5,7,9)
        SQL;

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->once())
            ->method('getEntityClass')
            ->willReturn(SimpleEntity::class);
        $collection->expects($this->exactly(3))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use ($expected) {
                static $counter = 0;
                $counter++;
                switch ($counter) {
                    case 1:
                        $index = 0;
                        $id    = 5;
                        break;
                    case 2:
                        $index = 1;
                        $id    = 7;
                        break;
                    case 3:
                        $index = 2;
                        $id    = 9;
                        break;
                }
                $this->assertEquals($key, $id);
                $this->assertEquals($expected[$index], $value);
            });

        $database = $this->createMock(Sql::class);
        $database->expects($this->once())
            ->method('getQueryData')
            ->with(
                $this->equalTo($sql),
                $this->equalTo([]),
                $this->equalTo(PDO::FETCH_CLASS),
                $this->equalTo(SimpleEntity::class)
            )->willReturn($expected);

        $meta = $this->createMock(Meta::class);
        $meta->expects($this->exactly(3))
            ->method('getTable')
            ->willReturn(new Table('simple_entities', 'id'));

        $metaFactory = $this->createMock(MetaFactory::class);
        $metaFactory->expects($this->exactly(3))
            ->method('get')
            ->willReturn($meta);

        $unit   = new Factory($database, $metaFactory);
        $actual = $unit->getEntityCollection($sql, $collection, [], false);

        $this->assertSame($collection, $actual);
    }

    public function testPersistWillInsert(): void
    {
        $meta = $this->createMock(Meta::class);
        $meta->expects($this->any())
            ->method('getTable')
            ->willReturn(new Table('simple_entities', 'id'));

        $database = $this->createMock(Sql::class);
        $database->expects($this->once())
            ->method('getInsertQuery')
            ->willReturn('INSERT INTO `simple_entities` (`id`, `name`) VALUES (?, ?)');
        $database->expects($this->once())
            ->method('getIdForInsert')
            ->willReturn(1);

        $metaFactory = $this->createMock(MetaFactory::class);
        $metaFactory->expects($this->any())
            ->method('get')
            ->willReturn($meta);

        $entity = $this->createMock(SimpleEntity::class);
        $entity->expects($this->once())
            ->method('setId')
            ->with(1);

        $unit = new Factory($database, $metaFactory);

        $unit->persist($entity);
    }

    public function testPersistWillUpdate(): void
    {
        $sql = <<<SQL
        UPDATE `simple_entities`
        SET `name` = ?
        WHERE `id` = ?
        SQL;

        $meta = $this->getMetaDataMock();

        $modified = $this->createMock(Modifications\Collection::class);
        $modified->expects($this->once())
            ->method('count')
            ->willReturn(1);
        $modified->expects($this->once())
            ->method('getNames')
            ->willReturn(new Text(['id', 'name']));

        $entity = $this->createMock(SimpleEntity::class);
        $entity->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $entity->expects($this->once())
            ->method('getName')
            ->willReturn('beta');
        $entity->expects($this->any())
            ->method('getModified')
            ->willReturn($modified);

        $database = $this->createMock(Sql::class);
        $database->expects($this->once())
            ->method('getUpdateQuery')
            ->with(
                $this->equalTo($meta),
                $this->equalTo($modified)
            )->willReturn($sql);
        $database->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo($sql),
                $this->equalTo(['beta', 1])
            )->willReturn(true);

        $metaFactory = $this->createMock(MetaFactory::class);
        $metaFactory->expects($this->any())
            ->method('get')
            ->willReturn($meta);

        $unit = new Factory($database, $metaFactory);
        $unit->persist($entity);
    }

    public function testPersistWillInsertCollection(): void
    {
        $sql = <<<SQL
        INSERT INTO `simple_entities` 
        (`name`) 
        VALUES (?),(?),(?)
        SQL;

        $meta = $this->getMetaDataMock();

        $entity1 = $this->createMock(SimpleEntity::class);
        $entity1->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $entity1->expects($this->once())
            ->method('getName')
            ->willReturn('beta');
        $entity1->expects($this->once())
            ->method('setId');
        $entity1->expects($this->once())
            ->method('resetModified');
        $entity2 = $this->createMock(SimpleEntity::class);
        $entity2->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $entity2->expects($this->once())
            ->method('getName')
            ->willReturn('delta');
        $entity2->expects($this->once())
            ->method('setId');
        $entity2->expects($this->once())
            ->method('resetModified');
        $entity3 = $this->createMock(SimpleEntity::class);
        $entity3->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $entity3->expects($this->once())
            ->method('getName')
            ->willReturn('gamma');
        $entity3->expects($this->once())
            ->method('setId');
        $entity3->expects($this->once())
            ->method('resetModified');

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->any())
            ->method('getEntityClass')
            ->willReturn(SimpleEntity::class);
        $collection->expects($this->any())
            ->method('count')
            ->willReturn(3);
        $collection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new ArrayIterator([$entity1, $entity2, $entity3]));
        $collection->expects($this->once())
            ->method('reduce')
            ->willReturn(true);

        $database = $this->createMock(Sql::class);
        $database->expects($this->once())
            ->method('getInsertQuery')
            ->with()->willReturn($sql);
        $database->expects($this->once())
            ->method('getIdForInsert')
            ->with(
                $this->equalTo($sql),
                $this->equalTo(['beta', 'delta', 'gamma']),
            )->willReturn(3);

        $metaFactory = $this->createMock(MetaFactory::class);
        $metaFactory->expects($this->any())
            ->method('get')
            ->willReturn($meta);

        $unit = new Factory($database, $metaFactory);
        $unit->persist($collection);
    }

    public function testPersistWillUpdateCollection(): void
    {
        $sql = <<<SQL
        UPDATE `simple_entities` 
        SET `name` = ?
        WHERE `id` = ?
        SQL;

        $meta = $this->getMetaDataMock();

        $modified = $this->createMock(Modifications\Collection::class);
        $modified->expects($this->any())
            ->method('count')
            ->willReturn(1);
        $modified->expects($this->any())
            ->method('getNames')
            ->willReturn(new Text(['id', 'name']));

        $entity1 = $this->createMock(SimpleEntity::class);
        $entity1->expects($this->once())
            ->method('getId')
            ->willReturn(5);
        $entity1->expects($this->once())
            ->method('getName')
            ->willReturn('beta');
        $entity1->expects($this->any())
            ->method('getModified')
            ->willReturn($modified);
        $entity1->expects($this->once())
            ->method('resetModified');
        $entity2 = $this->createMock(SimpleEntity::class);
        $entity2->expects($this->once())
            ->method('getId')
            ->willReturn(7);
        $entity2->expects($this->once())
            ->method('getName')
            ->willReturn('delta');
        $entity2->expects($this->any())
            ->method('getModified')
            ->willReturn($modified);
        $entity2->expects($this->once())
            ->method('resetModified');
        $entity3 = $this->createMock(SimpleEntity::class);
        $entity3->expects($this->once())
            ->method('getId')
            ->willReturn(9);
        $entity3->expects($this->once())
            ->method('getName')
            ->willReturn('gamma');
        $entity3->expects($this->any())
            ->method('getModified')
            ->willReturn($modified);
        $entity3->expects($this->once())
            ->method('resetModified');

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->any())
            ->method('getEntityClass')
            ->willReturn(SimpleEntity::class);
        $collection->expects($this->any())
            ->method('count')
            ->willReturn(3);
        $collection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new ArrayIterator([$entity1, $entity2, $entity3]));
        $collection->expects($this->exactly(2))
            ->method('reduce')
            ->willReturnCallback(function () {
                static $count = 0;
                $count++;
                if ($count === 1) {
                    return false;
                }
                return true;
            });

        $database = $this->createMock(Sql::class);
        $database->expects($this->exactly(3))
            ->method('getUpdateQuery')
            ->with(
                $this->equalTo($meta),
                $this->equalTo($modified)
            )->willReturn($sql);
        $database->expects($this->exactly(3))
            ->method('execute')
            ->willReturn(true);

        $metaFactory = $this->createMock(MetaFactory::class);
        $metaFactory->expects($this->any())
            ->method('get')
            ->willReturn($meta);

        $unit = new Factory($database, $metaFactory);

        $unit->persist($collection);
    }

    public function testPersistWillUpdateOrInsertCollection(): void
    {
        $meta = $this->getMetaDataMock();

        $modified = $this->createMock(Modifications\Collection::class);
        $modified->expects($this->any())
            ->method('count')
            ->willReturn(1);
        $modified->expects($this->any())
            ->method('getNames')
            ->willReturn(new Text(['id', 'name']));

        $entity1 = $this->createMock(SimpleEntity::class);
        $entity1->expects($this->any())
            ->method('getId')
            ->willReturn(5);
        $entity1->expects($this->once())
            ->method('getName')
            ->willReturn('beta');
        $entity1->expects($this->any())
            ->method('getModified')
            ->willReturn($modified);
        $entity1->expects($this->once())
            ->method('resetModified');
        $entity2 = $this->createMock(SimpleEntity::class);
        $entity2->expects($this->once())
            ->method('getId')
            ->willReturn(null);
        $entity2->expects($this->once())
            ->method('getName')
            ->willReturn('delta');
        $entity2->expects($this->once())
            ->method('setId')
            ->with($this->equalTo(7));
        $entity2->expects($this->once())
            ->method('resetModified');

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->any())
            ->method('getEntityClass')
            ->willReturn(SimpleEntity::class);
        $collection->expects($this->any())
            ->method('count')
            ->willReturn(2);
        $collection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new ArrayIterator([$entity1, $entity2]));
        $collection->expects($this->exactly(2))
            ->method('reduce')
            ->willReturn(false);

        $database = $this->createMock(Sql::class);
        $database->expects($this->once())
            ->method('getUpdateQuery')
            ->with(
                $this->equalTo($meta),
                $this->equalTo($modified)
            )->willReturn('UPDATE `simple_entities` SET `name` = ? WHERE `id` = ?');
        $database->expects($this->once())
            ->method('getInsertQuery')
            ->with()->willReturn('INSERT INTO `simple_entities` (`name`) VALUES (?)');
        $database->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $database->expects($this->once())
            ->method('getIdForInsert')
            ->willReturn(7);

        $metaFactory = $this->createMock(MetaFactory::class);
        $metaFactory->expects($this->any())
            ->method('get')
            ->willReturn($meta);

        $unit = new Factory($database, $metaFactory);

        $unit->persist($collection);
    }

    public function testPersistInsertWillThrowRuntimeException(): void
    {
        $entity = $this->createMock(SimpleEntity::class);
        $entity->expects($this->any())
            ->method('getId')
            ->willReturn(null);

        $database = $this->createMock(Sql::class);
        $database->expects($this->once())
            ->method('getIdForInsert')
            ->willReturn(0);

        $metaFactory = $this->createMock(MetaFactory::class);
        $metaFactory->expects($this->any())
            ->method('get')
            ->willReturn($this->getMetaDataMock());

        $unit = new Factory($database, $metaFactory);

        $this->expectException(RuntimeException::class);
        $unit->persist($entity);
    }

    public function testPersistUpdateWillThrowRuntimeException(): void
    {
        $modified = $this->createMock(Modifications\Collection::class);
        $modified->expects($this->any())
            ->method('count')
            ->willReturn(1);
        $modified->expects($this->any())
            ->method('getNames')
            ->willReturn(new Text(['id', 'name']));

        $entity = $this->createMock(SimpleEntity::class);
        $entity->expects($this->any())
            ->method('getId')
            ->willReturn(7);
        $entity->expects($this->any())
            ->method('getModified')
            ->willReturn($modified);

        $database = $this->createMock(Sql::class);
        $database->expects($this->once())
            ->method('getUpdateQuery')
            ->willReturn('UPDATE `simple_entities` SET `name` = ? WHERE `id` = ?');

        $metaFactory = $this->createMock(MetaFactory::class);
        $metaFactory->expects($this->any())
            ->method('get')
            ->willReturn($this->getMetaDataMock());

        $unit = new Factory($database, $metaFactory);

        $this->expectException(RuntimeException::class);
        $unit->persist($entity);
    }

    public function testDesist(): void
    {
        $sql  = 'DELETE FROM `simple_entities` WHERE `id` = ?';
        $meta = $this->getMetaDataMock();

        $entity = $this->createMock(SimpleEntity::class);
        $entity->expects($this->any())
            ->method('getId')
            ->willReturn(5);
        $entity->expects($this->any())
            ->method('getName')
            ->willReturn('delta');

        $database = $this->createMock(Sql::class);
        $database->expects($this->once())
            ->method('getDeleteQuery')
            ->with(
                $this->equalTo($meta),
                $this->equalTo(1)
            )->willReturn($sql);
        $database->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo($sql),
                $this->equalTo([5])
            )->willReturn(true);

        $metaFactory = $this->createMock(MetaFactory::class);
        $metaFactory->expects($this->any())
            ->method('get')
            ->willReturn($meta);

        $unit = new Factory($database, $metaFactory);

        $unit->desist($entity);
    }

    public function testDesistCollection(): void
    {
        $sql = 'DELETE FROM `simple_entities` WHERE `id` IN (?,?,?)';

        $entity1 = $this->createMock(SimpleEntity::class);
        $entity1->expects($this->any())
            ->method('getId')
            ->willReturn(5);
        $entity2 = $this->createMock(SimpleEntity::class);
        $entity2->expects($this->once())
            ->method('getId')
            ->willReturn(7);

        $meta = $this->getMetaDataMock();

        $database = $this->createMock(Sql::class);
        $database->expects($this->once())
            ->method('getDeleteQuery')
            ->willReturn($sql);
        $database->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        $metaFactory = $this->createMock(MetaFactory::class);
        $metaFactory->expects($this->any())
            ->method('get')
            ->willReturn($meta);

        $collection = $this->createMock(Collection::class);
        $collection->expects($this->any())
            ->method('count')
            ->willReturn(3);
        $collection->expects($this->any())
            ->method('getEntityClass')
            ->willReturn(SimpleEntity::class);
        $collection->expects($this->any())
            ->method('getIterator')
            ->willReturn(new ArrayIterator([$entity1, $entity2]));

        $unit = new Factory($database, $metaFactory);

        $unit->desist($collection);
    }

    public function testDesistWillThrowRuntimeException(): void
    {
        $sql    = 'DELETE FROM `simple_entities` WHERE `id` = ?';
        $entity = $this->createMock(SimpleEntity::class);
        $entity->expects($this->any())
            ->method('getId')
            ->willReturn(5);

        $database = $this->createMock(Sql::class);
        $database->expects($this->once())
            ->method('getDeleteQuery')
            ->willReturn($sql);
        $database->expects($this->once())
            ->method('execute')
            ->with(
                $this->equalTo($sql),
                $this->equalTo([5])
            )->willReturn(false);

        $metaFactory = $this->createMock(MetaFactory::class);
        $metaFactory->expects($this->any())
            ->method('get')
            ->willReturn($this->getMetaDataMock());

        $unit = new Factory($database, $metaFactory);

        $this->expectException(RuntimeException::class);
        $unit->desist($entity);
    }

    private function getMetaDataMock()
    {
        $meta = $this->createMock(Meta::class);
        $meta->expects($this->any())
            ->method('getTable')
            ->willReturn(new Table('simple_entities', 'id'));
        $meta->expects($this->any())
            ->method('getColumns')
            ->willReturn(new Columns\Collection([
                'id'   => new Column('id', 'simple_entities', true, getter: 'getId', setter: 'setId'),
                'name' => new Column('name', 'simple_entities', getter: 'getName', setter: 'setName'),
            ]));
        $meta->expects($this->any())
            ->method('getPersistables')
            ->willReturn(null);
        return $meta;
    }
}
