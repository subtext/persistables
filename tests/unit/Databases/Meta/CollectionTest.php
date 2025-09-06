<?php

namespace Subtext\Persistables\Tests\Unit\Databases\Meta;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Databases\Meta;
use Subtext\Persistables\Databases\Meta\Collection;

class CollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $mock = $this->createMock(Meta::class);
        $unit = new Collection();
        $unit->set('a', $mock);
        $this->assertSame($mock, $unit->get('a'));
    }

    public function testValidateCollection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Collection([null]);
    }
}
