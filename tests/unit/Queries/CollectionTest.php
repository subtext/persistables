<?php

namespace Subtext\Persistables\Tests\Unit\Queries;

use PHPUnit\Framework\TestCase;
use stdClass;
use Subtext\Persistables\Queries\Collection;
use Subtext\Persistables\Queries\SqlCommand;
use InvalidArgumentException;

class CollectionTest extends TestCase
{
    public function testValidateCollection(): void
    {
        $mock = $this->createMock(SqlCommand::class);

        $unit = new Collection();
        $unit->append($mock);

        $this->assertCount(1, $unit);
        $this->expectException(InvalidArgumentException::class);

        $unit->append(new stdClass());
    }
}
