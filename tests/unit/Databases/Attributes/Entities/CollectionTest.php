<?php

namespace Subtext\Persistables\Tests\Unit\Databases\Attributes\Entities;

use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Databases\Attributes\Entities\Collection;
use InvalidArgumentException;

class CollectionTest extends TestCase
{
    public function testCollection(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $unit = new Collection([null]);
    }
}
