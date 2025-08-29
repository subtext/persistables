<?php

namespace Subtext\Persistables\Tests\Unit\Databases\Attributes;

use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Databases\Attributes\Column;

class ColumnTest extends TestCase
{
    public function testColumn(): void
    {
        $unit = new Column('columnName', 'table', true, true, 'getter', 'setter');

        $this->assertSame('columnName', $unit->name);
        $this->assertSame('table', $unit->table);
        $this->assertSame(true, $unit->primary);
        $this->assertSame(true, $unit->readonly);
        $this->assertSame('getter', $unit->getter);
        $this->assertSame('setter', $unit->setter);
    }
}
