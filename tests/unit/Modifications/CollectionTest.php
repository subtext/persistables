<?php

namespace Subtext\Persistables\Tests\Unit\Modifications;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Subtext\Collections\Text;
use Subtext\Persistables\Modification;
use Subtext\Persistables\Modifications\Collection;

use function PHPUnit\Framework\assertEquals;

class CollectionTest extends TestCase
{
    public function testValidateCollection(): void
    {
        $mock = $this->createMock(Modification::class);
        $unit = new Collection();
        $unit->set('x', $mock);

        $this->assertSame($mock, $unit->get('x'));
        $this->expectException(InvalidArgumentException::class);

        $unit->append(0);
    }

    public function testCanGetModificationNames(): void
    {
        $expected = new Text(['a', 'b', 'c']);
        $mod1     = $this->createMock(Modification::class);
        $mod1->expects($this->once())
            ->method('getName')
            ->willReturn('a');
        $mod2 = $this->createMock(Modification::class);
        $mod2->expects($this->once())
            ->method('getName')
            ->willReturn('b');
        $mod3 = $this->createMock(Modification::class);
        $mod3->expects($this->once())
            ->method('getName')
            ->willReturn('c');

        $unit = new Collection([$mod1, $mod2, $mod3]);

        $actual = $unit->getNames();

        $this->assertEquals($expected, $actual);
    }
}
