<?php

namespace Subtext\Persistables\Tests\Unit\Databases\Connections;

use PDO;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Subtext\Persistables\Databases\Connection;
use Subtext\Persistables\Databases\Connections\Auth;
use Subtext\Persistables\Databases\Connections\MySql;
use Subtext\Persistables\Databases\SqlGenerator;

class MySqlTest extends TestCase
{
    public function testConnection(): void
    {
        $unit = MySql::getInstance(new: true);

        $this->assertInstanceOf(Connection::class, $unit);
        $this->assertInstanceOf(PDO::class, $unit->getPdo());
        $this->assertInstanceOf(SqlGenerator::class, $unit->getSqlGenerator());

        $actual = MySql::getInstance();
        $this->assertSame($unit, $actual);
    }

    public function testWillThrowException(): void
    {
        $unit = MySql::getInstance(new Auth('', '', '', '', ''), true);

        $this->expectException(RuntimeException::class);
        $unit->getPdo();
    }
}
