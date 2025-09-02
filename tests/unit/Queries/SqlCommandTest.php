<?php

namespace Subtext\Persistables\Tests\Unit\Queries;

use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Queries\SqlCommand;

class SqlCommandTest extends TestCase
{
    public function testSqlCommand(): void
    {
        $query = 'SELECT * FROM `users` WHERE id = ?';
        $data  = [5];

        $unit = new SqlCommand($query, $data);

        $this->assertEquals($query, $unit->getQuery());
        $this->assertEquals($data, $unit->getData());
    }
}
