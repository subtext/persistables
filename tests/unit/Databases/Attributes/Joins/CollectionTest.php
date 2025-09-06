<?php

namespace Subtext\Persistables\Tests\Unit\Databases\Attributes\Joins;

use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Databases\Attributes\Joins\Collection;
use InvalidArgumentException;

class CollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $unit = new Collection([null]);
    }
}
