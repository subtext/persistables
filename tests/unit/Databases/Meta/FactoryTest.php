<?php

namespace Subtext\Persistables\Tests\Unit\Databases\Meta;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Subtext\Persistable\Tests\Unit\Fixtures\BadUnionEntity;
use Subtext\Persistables\Databases\Meta\Factory as MetaFactory;
use Subtext\Persistables\Tests\Unit\Fixtures\BadJoinType;
use Subtext\Persistables\Tests\Unit\Fixtures\SimpleEntity;
use Subtext\Persistables\Tests\Unit\Fixtures\WithCustomAccessor;
use Subtext\Persistables\Tests\Unit\Fixtures\ReadonlyColumnEntity;
use Subtext\Persistables\Tests\Unit\Fixtures\WithEntityExplicit;
use Subtext\Persistables\Tests\Unit\Fixtures\WithEntityImplicitNullable;
use Subtext\Persistables\Tests\Unit\Fixtures\WithEntityUnion;
use Subtext\Persistables\Tests\Unit\Fixtures\BadAccessorEntity;
use Subtext\Persistables\Tests\Unit\Fixtures\NoColumnsEntity;
use Subtext\Persistables\Tests\Unit\Fixtures\MissingTable;

class FactoryTest extends TestCase
{
    public function testGetInstanceResetsWhenNewIsTrue(): void
    {
        $f1  = MetaFactory::getInstance(true);
        $m1a = $f1->get(SimpleEntity::class);
        $m1b = $f1->get(SimpleEntity::class);
        $this->assertSame($m1a, $m1b, 'Cached meta should be reused within the same instance');

        $f2 = MetaFactory::getInstance(true);
        $m2 = $f2->get(SimpleEntity::class);
        $this->assertNotSame($m1a, $m2, 'Fresh instance should rebuild meta cache');
    }

    public function testMissingTableThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MetaFactory::getInstance(true)->get(MissingTable::class);
    }

    public function testNoColumnsThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MetaFactory::getInstance(true)->get(NoColumnsEntity::class);
    }

    public function testBadJoinTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MetaFactory::getInstance(true)->get(BadJoinType::class);
    }

    public function testSimpleEntityColumnsAndAccessorsAreInferred(): void
    {
        $meta = MetaFactory::getInstance(true)->get(SimpleEntity::class);

        $this->assertSame('simple_entity', $meta->getTable()->name);
        $cols = $meta->getColumns();
        $this->assertTrue($cols->has('id'));
        $this->assertTrue($cols->has('name'));

        $id = $cols->get('id');
        $this->assertTrue($id->primary);
        $this->assertSame('getId', $id->getter);
        $this->assertSame('setId', $id->setter);

        $name = $cols->get('name');
        $this->assertSame('name', $name->name);
        $this->assertSame('getName', $name->getter);
        $this->assertSame('setName', $name->setter);
    }

    public function testReadonlyColumnHasNoSetter(): void
    {
        $meta    = MetaFactory::getInstance(true)->get(ReadonlyColumnEntity::class);
        $created = $meta->getColumns()->get('createdAt');
        $this->assertTrue($created->readonly);
        $this->assertNotNull($created->getter);
        $this->assertNull($created->setter, 'Readonly column should not have a setter');
    }

    public function testExplicitCustomAccessorIsUsed(): void
    {
        $meta = MetaFactory::getInstance(true)->get(WithCustomAccessor::class);
        $id   = $meta->getColumns()->get('id');
        $this->assertSame('fetchId', $id->getter);
        $this->assertSame('storeId', $id->setter);
    }

    public function testBadAccessorThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MetaFactory::getInstance(true)->get(BadAccessorEntity::class);
    }

    public function testEntityExplicitClassAndForeignAndAccessors(): void
    {
        $meta = MetaFactory::getInstance(true)->get(WithEntityExplicit::class);
        $ents = $meta->getPersistables();
        $this->assertNotNull($ents);
        $this->assertTrue($ents->has('child'));

        $e = $ents->get('child');
        $this->assertSame(SimpleEntity::class, $e->class);
        $this->assertSame('entityId', $e->foreign);
        $this->assertFalse($e->nullable);
        $this->assertSame('getChild', $e->getter);
        $this->assertSame('setChild', $e->setter);
    }

    public function testEntityImplicitFromPropertyTypeSetsNullable(): void
    {
        $meta = MetaFactory::getInstance(true)->get(WithEntityImplicitNullable::class);
        $e    = $meta->getPersistables()->get('child');
        $this->assertSame(SimpleEntity::class, $e->class);
        $this->assertTrue($e->nullable, 'Nullable typed entity should set nullable=true');
    }

    public function testEntityUnionTypeInferenceChoosesPersistable(): void
    {
        $meta = MetaFactory::getInstance(true)->get(WithEntityUnion::class);
        $e    = $meta->getPersistables()->get('child');
        $this->assertSame(SimpleEntity::class, $e->class, 'Union type should select the Persistable subtype');
    }

    public function testEntityUnionWithNoPersistableThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MetaFactory::getInstance(true)->get(BadUnionEntity::class);
    }
}
