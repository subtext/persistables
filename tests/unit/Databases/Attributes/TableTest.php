<?php

namespace Subtext\Persistables\Tests\Unit\Databases\Attributes;

use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Databases\Attributes\Table;

class TableTest extends TestCase
{
    public function testTable(): void
    {
        $unit = new Table('table', 'primaryKey');

        $this->assertSame('table', $unit->name);
        $this->assertSame('primaryKey', $unit->primaryKey);
    }
}
