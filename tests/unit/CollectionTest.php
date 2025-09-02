<?php

namespace Subtext\Persistables\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Collection;
use Subtext\Persistables\Tests\Unit\Fixtures\SimpleEntity;
use InvalidArgumentException;

class CollectionTest extends TestCase
{
    public function testValidateCollection(): void
    {
        $unit = new class () extends Collection {
            public function getEntityClass(): string
            {
                return SimpleEntity::class;
            }
        };

        $expected = new SimpleEntity();

        $unit->append($expected);

        $this->assertSame($expected, $unit->getFirst());
        $this->expectException(InvalidArgumentException::class);

        $unit->append($this->createMock(SimpleEntity::class));
    }
}
