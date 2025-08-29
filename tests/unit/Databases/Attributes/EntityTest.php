<?php

namespace Subtext\Persistables\Tests\Unit\Databases\Attributes;

use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Databases\Attributes\Entity;
use Subtext\Persistables\Databases\Attributes\PersistOrder;

class EntityTest extends TestCase
{
    public function testEntity(): void
    {
        $unit = new Entity(
            'className',
            'foreignKey',
            true,
            true,
            'getter',
            'setter'
        );

        $this->assertSame('className', $unit->class);
        $this->assertSame('foreignKey', $unit->foreign);
        $this->assertSame(true, $unit->nullable);
        $this->assertSame(true, $unit->collection);
        $this->assertSame('getter', $unit->getter);
        $this->assertSame('setter', $unit->setter);
        $this->assertSame(PersistOrder::AFTER, $unit->order);
    }
}
