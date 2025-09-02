<?php

namespace Subtext\Persistables\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Modification;

class ModificationTest extends TestCase
{
    public function testModification(): void
    {
        $name = 'foobar';
        $old  = 1;
        $new  = 2;

        $unit = Modification::from($name, $old, $new);

        $this->assertEquals($name, $unit->getName());
        $this->assertEquals($old, $unit->getOldValue());
        $this->assertEquals($new, $unit->getNewValue());
    }
}
