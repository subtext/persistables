<?php

namespace Subtext\Persistables\Tests\Unit\Databases;

use ArrayIterator;
use Exception;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Databases\Attributes\Columns;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Connection;
use Subtext\Persistables\Databases\Meta;
use Subtext\Persistables\Databases\Sql;
use Subtext\Persistables\Databases\SqlGenerator;
use Subtext\Persistables\Modifications;
use Subtext\Persistables\Persistable;
use Subtext\Persistables\Queries;
use Subtext\Persistables\Queries\SqlCommand;

class SqlTest extends TestCase
{
    protected Connection $connection;
    protected PDO $pdo;
    protected PDOStatement $stmt;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->pdo        = $this->createMock(PDO::class);
        $this->stmt       = $this->createMock(PDOStatement::class);
    }

    public function testDatabaseInstanceIsSingleton(): void
    {
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $actual   = Sql::getInstance($this->connection, true);
        $expected = Sql::getInstance(null);

        $this->assertSame($expected, $actual);
    }

    public function testWillThrowExceptionForBadConnection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Sql::getInstance(null, true);
    }

    public function testWillPassThroughSqlGenerator(): void
    {
        $expected = [
            'select' => 'SELECT',
            'insert' => 'INSERT INTO',
            'update' => 'UPDATE',
            'delete' => 'DELETE',
        ];
        $generator = $this->createMock(SqlGenerator::class);
        $generator->expects($this->once())
            ->method('getSelectQuery')
            ->willReturn($expected['select']);
        $generator->expects($this->once())
            ->method('getInsertQuery')
            ->willReturn($expected['insert']);
        $generator->expects($this->once())
            ->method('getUpdateQuery')
            ->willReturn($expected['update']);
        $generator->expects($this->once())
            ->method('getDeleteQuery')
            ->willReturn($expected['delete']);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $this->connection->expects($this->once())
            ->method('getSqlGenerator')
            ->willReturn($generator);

        $unit = Sql::getInstance($this->connection, true);
        $meta = new Meta(new Table('table'), new Columns\Collection(), null, null);
        $mods = new Modifications\Collection();
        $this->assertSame($expected['select'], $unit->getSelectQuery($meta));
        $this->assertSame($expected['insert'], $unit->getInsertQuery($meta));
        $this->assertSame($expected['update'], $unit->getUpdateQuery($meta, $mods));
        $this->assertSame($expected['delete'], $unit->getDeleteQuery($meta));

    }

    public function testCanGetArrayOfObjectsWithGetQueryData(): void
    {
        $sql = "SELECT * FROM `orders`";
        $this->stmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_OBJ)
            ->willReturn([(object)['id' => 5, 'status' => 'fulfilled']]);
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $db     = Sql::getInstance($this->connection, true);
        $actual = $db->getQueryData($sql);

        $this->assertIsArray($actual);
        $this->assertIsObject($actual[0]);
    }

    public function testCanGetArrayOfEntitiesWithGetQueryData(): void
    {
        $mock1 = $this->createMock(Persistable::class);
        $mock2 = $this->createMock(Persistable::class);
        $sql   = "SELECT * FROM `orders`";
        $this->stmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $this->stmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_CLASS, Persistable::class)
            ->willReturn([$mock1, $mock2]);
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $db     = Sql::getInstance($this->connection, true);
        $actual = $db->getQueryData($sql, [], PDO::FETCH_CLASS, Persistable::class);

        $this->assertIsArray($actual);
        $this->assertInstanceOf(Persistable::class, $actual[0]);
    }

    public function testCanGetStringWithGetQueryResult(): void
    {
        $sql = "SELECT COUNT(*) FROM `customers`";
        $this->stmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn('string');
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $db     = Sql::getInstance($this->connection, true);
        $actual = $db->getQueryResult($sql);

        $this->assertIsString($actual);
    }

    public function testCanGetIntegerWithGetQueryResult(): void
    {
        $expected = 500;
        $sql      = "SELECT COUNT(*) FROM `customers`";
        $this->stmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(strval($expected));
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);

        $unit   = Sql::getInstance($this->connection, true);
        $actual = $unit->getQueryResult($sql);

        $this->assertEquals($expected, $actual);
    }

    public function testCanGetFloatWithGetQueryResult(): void
    {
        $expected = 3.14;
        $sql      = "SELECT AVG(`total`) FROM `orders`";
        $this->stmt->expects($this->once())
            ->method('fetchColumn')
            ->willReturn(strval($expected));
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);

        $unit   = Sql::getInstance($this->connection, true);
        $actual = $unit->getQueryResult($sql);

        $this->assertEquals($expected, $actual);
    }

    public function testGetQueryResultWillReturnEmptyStringOnPdoException(): void
    {
        $sql = "SELECT `a`, `b`, `c` FROM `table` WHERE `id` = 1";
        $this->stmt->expects($this->once())
            ->method('execute')
            ->willThrowException(new PDOException());
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);

        $unit   = Sql::getInstance($this->connection, true);
        $actual = $unit->getQueryResult($sql);

        $this->assertEquals('', $actual);
    }

    public function testCanGetArrayOfStringsWithGetQueryRow(): void
    {
        $sql      = "SELECT `a`, `b`, `c` FROM `table` WHERE `id` = 1";
        $expected = ['1', '2', '3'];

        $this->stmt->expects($this->once())
            ->method('fetch')
            ->willReturn($expected);
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $db     = Sql::getInstance($this->connection, true);
        $actual = $db->getQueryRow($sql);

        $this->assertIsArray($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testCanGetEntityWithGetQueryRow(): void
    {
        $sql  = "SELECT `a`, `b`, `c` FROM `table` WHERE `id` = 1";
        $mock = $this->createMock(Persistable::class);
        $this->stmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $this->stmt->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_CLASS, Persistable::class)
            ->willReturn([$mock]);
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);

        $unit   = Sql::getInstance($this->connection, true);
        $actual = $unit->getQueryRow($sql, [], PDO::FETCH_CLASS, Persistable::class);

        $this->assertInstanceOf(Persistable::class, $actual);
    }

    public function testCanGetArrayOfStringsWithGetQueryColumn(): void
    {
        $sql      = "SELECT `x` FROM `table` WHERE `id` IN(1, 3, 5, 7, 9)";
        $expected = ['2', '4', '6', '8', '0'];
        $values   = ['2', '4', '6', '8', '0', false];

        $this->stmt->expects($this->exactly(6))
            ->method('fetchColumn')
            ->willReturnCallback(function () use (&$values) {
                return array_shift($values);
            });
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);

        $db     = Sql::getInstance($this->connection, true);
        $actual = $db->getQueryColumn($sql);

        $this->assertIsArray($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testCanGetIdForInsertQuery(): void
    {
        $sql      = "INSERT INTO `table` VALUES (NULL, 'a', 'b', 'c')";
        $expected = '1000';

        $this->stmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $this->pdo->expects($this->once())
            ->method('lastInsertId')
            ->willReturn($expected);
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $db     = Sql::getInstance($this->connection, true);
        $actual = $db->getIdForInsert($sql);

        $this->assertIsInt($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetIdForInsertWillReturnZeroIfSomethingGoesWrong(): void
    {
        $sql = "INSERT INTO `table` VALUES (NULL, 'alpha'), (NULL, 'beta')";
        $this->stmt
            ->expects($this->once())
            ->method('execute')
            ->willReturn(false);
        $this->stmt
            ->expects($this->once())
            ->method('errorInfo')
            ->willReturn(['','','']);
        $this->pdo
            ->expects($this->once())
            ->method('prepare')
            ->with($this->equalTo($sql))
            ->willReturn($this->stmt);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);

        $unit = Sql::getInstance($this->connection, true);
        $this->assertEquals(0, $unit->getIdForInsert($sql));
    }

    public function testGetIdForInsertWillThrowExceptionForBadQuery(): void
    {
        $sql  = "SELECT * FROM `table`";
        $unit = Sql::getInstance($this->connection, true);
        $this->expectException(InvalidArgumentException::class);
        $unit->getIdForInsert($sql);
    }

    public function testCanGetCountForUpdateQuery(): void
    {
        $sql      = "UPDATE `table` SET `a` = 2, `b` = 2, `c` = 3";
        $expected = 500;

        $this->stmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $this->stmt->expects($this->once())
            ->method('rowCount')
            ->willReturn($expected);
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $db     = Sql::getInstance($this->connection, true);
        $actual = $db->getNumRowsAffectedForUpdate($sql);

        $this->assertIsInt($actual);
        $this->assertEquals($expected, $actual);
    }

    public function testGetNumRowsAffectedWillThrowExceptionForBadQuery(): void
    {
        $sql  = "SELECT * FROM `table`";
        $unit = Sql::getInstance($this->connection, true);
        $this->expectException(InvalidArgumentException::class);
        $unit->getNumRowsAffectedForUpdate($sql);
    }

    public function testGetNumRowsAffectedWillReturnZeroIfSomethingGoesWrong(): void
    {
        $sql = "UPDATE `table` SET `column_one` = 1";
        $this->stmt
            ->expects($this->once())
            ->method('execute')
            ->willReturn(false);
        $this->stmt
            ->expects($this->once())
            ->method('errorInfo')
            ->willReturn(['','','']);
        $this->pdo
            ->expects($this->once())
            ->method('prepare')
            ->with($this->equalTo($sql))
            ->willReturn($this->stmt);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $unit = Sql::getInstance($this->connection, true);
        $this->assertEquals(0, $unit->getNumRowsAffectedForUpdate($sql));
    }

    public function testGetNumRowsAffectedWillReturnZeroOnPDOException(): void
    {
        $sql = "UPDATE `table` SET `column_one` = 1";
        $this->pdo
            ->expects($this->once())
            ->method('prepare')
            ->with($this->equalTo($sql))
            ->willReturn(false);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $unit = Sql::getInstance($this->connection, true);
        $this->assertEquals(0, $unit->getNumRowsAffectedForUpdate($sql));
    }

    public function testExecuteWillInvokeTheDatabase(): void
    {
        $sql = "SELECT * FROM `table`";
        $this->stmt
            ->expects($this->once())
            ->method('execute')
            ->with($this->equalTo([]))
            ->willReturn(true);
        $this->pdo
            ->expects($this->once())
            ->method('prepare')
            ->with($this->equalTo($sql))
            ->willReturn($this->stmt);
        $this->connection
            ->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $unit   = Sql::getInstance($this->connection, true);
        $actual = $unit->execute($sql);
        $this->assertTrue($actual);
    }

    public function testExecuteWillUseComplexParameters(): void
    {
        $sql    = "SELECT * FROM `table` WHERE `name` = ? LIMIT ? OFFSET ?";
        $params = [
            5,
            (object) [
                'value' => 50,
                'type'  => PDO::PARAM_INT,
            ],
            (object) [
                'value' => 100,
                'type'  => PDO::PARAM_INT,
            ]
        ];

        $this->stmt
            ->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $this->pdo
            ->expects($this->once())
            ->method('prepare')
            ->with($this->equalTo($sql))
            ->willReturn($this->stmt);
        $this->connection
            ->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $unit   = Sql::getInstance($this->connection, true);
        $actual = $unit->execute($sql, $params);
        $this->assertTrue($actual);

    }

    public function testExecuteTransactionWillInvokeTheDatabase(): void
    {
        $query1 = $this->createMock(SqlCommand::class);
        $query1->expects($this->once())
            ->method('getQuery')
            ->willReturn('UPDATE `table` SET `name` = ? WHERE `id` = ?');
        $query1->expects($this->once())
            ->method('getData')
            ->willReturn([5]);
        $query2 = $this->createMock(SqlCommand::class);
        $query2->expects($this->once())
            ->method('getQuery')
            ->willReturn('DELETE FROM `table` WHERE `id` = ?`');
        $query2->expects($this->once())
            ->method('getData')
            ->willReturn([91]);
        $collection = $this->createMock(Queries\Collection::class);
        $collection->method('getIterator')
            ->willReturn(new ArrayIterator([$query1, $query2]));
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $this->pdo
            ->expects($this->once())
            ->method('beginTransaction');
        $this->pdo
            ->expects($this->exactly(2))
            ->method('prepare')
            ->willReturn($this->stmt);
        $this->pdo
            ->expects($this->once())
            ->method('commit');
        $this->stmt
            ->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);

        $unit = Sql::getInstance($this->connection, true);
        $unit->executeTransaction($collection);
    }

    public function testExecuteTransactionWillRollbackOnError(): void
    {
        $sql    = "UPDATE `table` SET `name` = ? WHERE `id` = ?";
        $query1 = $this->createMock(SqlCommand::class);
        $query1->expects($this->once())
            ->method('getQuery')
            ->willReturn($sql);
        $query1->expects($this->once())
            ->method('getData')
            ->willReturn([20]);
        $query2     = $this->createMock(SqlCommand::class);
        $collection = $this->createMock(Queries\Collection::class);
        $collection->method('getIterator')
            ->willReturn(new ArrayIterator([$query1, $query2]));
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $this->pdo
            ->expects($this->once())
            ->method('beginTransaction');
        $this->pdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);
        $this->pdo
            ->expects($this->once())
            ->method('rollback');
        $this->stmt
            ->expects($this->once())
            ->method('execute')
            ->willReturn(false);

        $unit = Sql::getInstance($this->connection, true);
        $unit->executeTransaction($collection);
    }

    public function testExecuteTransactionWillRollbackOnException(): void
    {
        $i      = 0;
        $sql    = "UPDATE `table` SET `name` = ? WHERE `id` = ?";
        $query1 = $this->createMock(SqlCommand::class);
        $query1->expects($this->once())
            ->method('getQuery')
            ->willReturn($sql);
        $query1->expects($this->once())
            ->method('getData')
            ->willReturn([20]);
        $query2 = $this->createMock(SqlCommand::class);
        $query2->expects($this->once())
            ->method('getQuery')
            ->willReturn($sql);
        $query2->expects($this->once())
            ->method('getData')
            ->willReturn([21]);
        $collection = $this->createMock(Queries\Collection::class);
        $collection->method('getIterator')
            ->willReturn(new ArrayIterator([$query1, $query2]));
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $this->pdo
            ->expects($this->once())
            ->method('beginTransaction');
        $this->pdo
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);
        $this->pdo
            ->expects($this->once())
            ->method('rollback');
        $this->pdo
            ->expects($this->once())
            ->method('commit')
            ->willThrowException(new PDOException());
        $this->stmt
            ->expects($this->exactly(2))
            ->method('execute')
            ->willReturn(true);
        $unit = Sql::getInstance($this->connection, true);
        $unit->executeTransaction($collection);
    }

    public function testExecuteWillHandleQueryFailures(): void
    {
        $this->pdo
            ->expects($this->once())
            ->method('prepare')
            ->with($this->equalTo(""))
            ->willReturn(false);
        $this->connection
            ->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $unit   = Sql::getInstance($this->connection, true);
        $actual = $unit->execute("");
        $this->assertFalse($actual);
    }

    public function testExecuteWillHandlePDOExceptions(): void
    {
        $sql = "SELECT * FROM `missing_table`";
        $this->stmt
            ->expects($this->once())
            ->method('execute')
            ->willThrowException(new PDOException());
        $this->pdo
            ->expects($this->once())
            ->method('prepare')
            ->with($this->equalTo($sql))
            ->willReturn($this->stmt);
        $this->connection
            ->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $unit   = Sql::getInstance($this->connection, true);
        $actual = $unit->execute($sql);
        $this->assertFalse($actual);
    }

    public function testCanQuoteString(): void
    {
        $argument = 'whatever';
        $expected = "'whatever'";

        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);

        $this->pdo->expects($this->once())
            ->method('quote')
            ->with($this->equalTo($argument))
            ->willReturn($expected);

        $unit   = Sql::getInstance($this->connection, true);
        $actual = $unit->quote($argument);

        $this->assertEquals($expected, $actual);
    }

    public function testQuoteWillReturnNullOnError(): void
    {
        $argument = "'foobar'";
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);

        $this->pdo->expects($this->once())
            ->method('quote')
            ->with($this->equalTo($argument))
            ->willReturn(false);

        $unit   = Sql::getInstance($this->connection, true);
        $actual = $unit->quote($argument);

        $this->assertNull($actual);

    }

    public function testDoesReturnErrorInfoOnExceptions(): void
    {
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->with("")
            ->willReturn(false);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $unit   = Sql::getInstance($this->connection, true);
        $actual = $unit->getQueryData("");

        $this->assertEquals([], $actual);
        $errors = $unit->getErrors();
        $this->assertIsArray($errors);
    }

    public function testDoesCatchExceptionsAndReturnEmptyArray(): void
    {
        $expected = [
            'msg'  => 'There was an error',
            'code' => 500,
        ];
        $this->stmt->expects($this->once())
            ->method('execute')
            ->willReturn(true);
        $this->stmt->expects($this->once())
            ->method('fetchAll')
            ->will($this->throwException(
                new Exception($expected['msg'], $expected['code'])
            ));
        $this->pdo->expects($this->once())
            ->method('prepare')
            ->willReturn($this->stmt);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $unit   = Sql::getInstance($this->connection, true);
        $data   = $unit->getQueryData("SELECT 1");
        $actual = $unit->getErrors();

        $this->assertEquals([], $data);
        $this->assertIsArray($actual);
        $this->assertEquals($expected['msg'], $actual[0]->msg);
        $this->assertEquals($expected['code'], $actual[0]->code);
    }

    public function testDoesCatchAndRecordDatabaseErrors(): void
    {
        $sql = "SELECT * FROM `missing-table`";
        $this->stmt
            ->expects($this->once())
            ->method('execute')
            ->willReturn(false);
        $this->stmt
            ->expects($this->once())
            ->method('errorInfo')
            ->willReturn([
                '12345',
                0,
                'error message'
            ]);
        $this->pdo
            ->expects($this->once())
            ->method('prepare')
            ->with($this->equalTo($sql))
            ->willReturn($this->stmt);
        $this->connection->expects($this->once())
            ->method('getPdo')
            ->willReturn($this->pdo);
        $unit = Sql::getInstance($this->connection, true);
        $unit->execute($sql);
    }
}
