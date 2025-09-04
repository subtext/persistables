<?php

namespace Subtext\Persistables\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Collection;
use Subtext\Persistables\Container;
use Subtext\Persistables\Persistable;
use Subtext\Persistables\Tests\Unit\Fixtures\SimpleEntity;

class ContainerTest extends TestCase
{
    public function testContainer(): void
    {
        $unit = new Container();

        $collection = new class() extends Collection
        {
            public function getEntityClass(): string
            {
                return SimpleEntity::class;
            }
        };

        $persistable = new class() extends Persistable
        {
            public function jsonSerialize(): mixed
            {
                return (object) [];
            }
        };

        $foobar = new class()
        {};

        $this->assertCount(0, $unit);
        $unit->append($collection);
        $unit->append($persistable);
        $this->assertCount(2, $unit);

        $this->expectException(InvalidArgumentException::class);
        $unit->append($foobar);
    }

    public function testContainerEntityClass(): void
    {
        $unit = new Container();
        $this->assertEquals(Collection::class, $unit->getEntityClass());
    }
}
