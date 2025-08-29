<?php

namespace Subtext\Persistables\Tests\Unit\Databases\Attributes\Columns;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Databases\Attributes\Columns\Collection;

class CollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $unit = new Collection([null]);
    }
}
