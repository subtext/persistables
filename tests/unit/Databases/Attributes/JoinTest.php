<?php

namespace Subtext\Persistables\Tests\Unit\Databases\Attributes;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Databases\Attributes\Join;

class JoinTest extends TestCase
{
    public function testJoin(): void
    {
        $unit = new Join('JOIN', 'table', 'key', 'foreign', 'target');

        $this->assertSame('JOIN', $unit->type);
        $this->assertSame('table', $unit->table);
        $this->assertSame('key', $unit->key);
        $this->assertSame('foreign', $unit->foreign);
        $this->assertSame('target', $unit->target);
    }

    public function testWillThrowExceptionForBadJoin(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Join('type', 'table', 'key');
    }
}
