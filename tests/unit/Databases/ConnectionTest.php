<?php

namespace Subtext\Persistables\Tests\Unit\Databases;

use PDO;
use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Databases\Connection;
use Subtext\Persistables\Databases\SqlGenerator;

class ConnectionTest extends TestCase
{
    public function testConnection(): void
    {
        $pdo = $this->createMock(PDO::class);
        $fn = function(
            string $db,
            string $host,
            string $user,
            string $pass,
            string $charset) use ($pdo) {
            return $pdo;
        };

        $unit = new Connection(
            'database',
            'host',
            'user',
            'password',
            'driver',
            'charset',
            $fn
        );

        $this->assertInstanceOf(PDO::class, $unit->getPdo());
        $this->assertInstanceOf(SqlGenerator::class, $unit->getSqlGenerator());
    }

    public function testWillReturnNull(): void
    {
        $unit = new Connection(
            'database',
            'hostname',
            'username',
            'password',
            'driver',
            'utf8',
            function () {
            return null;
        });

        $this->assertNull($unit->getPdo());
    }
}
