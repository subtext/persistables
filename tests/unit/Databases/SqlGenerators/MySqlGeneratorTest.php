<?php

namespace Subtext\Persistables\Tests\Unit\Databases\SqlGenerators;

use PHPUnit\Framework\TestCase;
use Subtext\Collections\Text;
use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Columns;
use Subtext\Persistables\Databases\Attributes\Join;
use Subtext\Persistables\Databases\Attributes\Joins;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Meta;
use Subtext\Persistables\Databases\SqlGenerators\MySqlGenerator;
use Subtext\Persistables\Modifications;

class MySqlGeneratorTest extends TestCase
{
    public function testMySqlGeneratorIsSingleton(): void
    {
        $unit   = MySqlGenerator::getInstance();
        $actual = MySqlGenerator::getInstance();

        $this->assertSame($unit, $actual);
    }

    public function testGetSelectQuery(): void
    {
        $expected = <<<SQL
        SELECT `alpha`.`alpha_id` AS `id`,
        `alpha`.`alpha_name` AS `name`
        FROM `alpha`
        WHERE `alpha`.`alpha_id` = ?
        SQL;
        $columns = [
            'id'   => new Column('alpha_id'),
            'name' => new Column('alpha_name'),
        ];
        $unit = MySqlGenerator::getInstance();
        $meta = new Meta(
            new Table('alpha', 'alpha_id'),
            new Columns\Collection($columns),
            null,
            null,
        );

        $actual = $unit->getSelectQuery($meta);

        $this->assertSame($expected, $actual);
    }

    public function testGetSelectQueryWithJoins(): void
    {
        $expected = <<<SQL
        SELECT `greek`.`id`,
        `greek`.`alpha_name` AS `alpha`,
        `greek`.`beta_name` AS `beta`,
        `greek`.`gamma_name` AS `gamma`,
        `roman`.`d` AS `delta`
        FROM `greek`
        JOIN `roman` ON `greek`.`id` = `roman`.`greek_id`
        WHERE `greek`.`id` = ?
        SQL;
        $columns = [
            'id'    => new Column(),
            'alpha' => new Column('alpha_name'),
            'beta'  => new Column('beta_name'),
            'gamma' => new Column('gamma_name'),
            'delta' => new Column('d', 'roman', true),
        ];
        $joins = [
            new Join('JOIN', 'roman', 'id', 'greek_id'),
        ];
        $meta = new Meta(
            new Table('greek', 'id'),
            new Columns\Collection($columns),
            new Joins\Collection($joins),
            null
        );

        $unit = MySqlGenerator::getInstance();
        $actual = $unit->getSelectQuery($meta);

        $this->assertEquals($expected, $actual);

    }

    public function testGetSelectQueryWillFormatColumnNames(): void
    {
        $expected = <<<SQL
        SELECT `greek`.`alpha`,
        `greek`.`beta`,
        `greek`.`gamma`,
        `greek`.`delta`
        FROM `greek`
        WHERE `delta` = 12
        SQL;
        $columns = [
            'alpha' => new Column(),
            'beta'  => new Column(),
            'gamma' => new Column(),
            'delta' => new Column(),
        ];
        $meta = new Meta(
            new Table('greek', 'alpha'),
            new Columns\Collection($columns),
            null,
            null
        );

        $unit = MySqlGenerator::getInstance();

        $actual = $unit->getSelectQuery($meta, "`delta` = 12");

        $this->assertSame($expected, $actual);

    }

    public function testGetInsertQuery(): void
    {
        $expected = <<<SQL
        INSERT INTO `alpha` (`alpha_id`, `alpha_name`)
        VALUES (?,?)
        ON DUPLICATE KEY UPDATE `alpha_id` = LAST_INSERT_ID(`alpha_id`)
        SQL;
        $columns = [
            'id'   => new Column('alpha_id'),
            'name' => new Column('alpha_name'),
        ];
        $unit = MySqlGenerator::getInstance();
        $meta = new Meta(
            new Table('alpha', 'alpha_id'),
            new Columns\Collection($columns),
            null,
            null,
        );
        $actual = $unit->getInsertQuery($meta);

        $this->assertSame($expected, $actual);
    }

    public function testGetUpdateQuery(): void
    {
        $expected = <<<SQL
        UPDATE `greek` SET `greek`.`alpha` = ?,
        `greek`.`gamma` = ?,
        `greek`.`epsilon` = ?
        WHERE `id` = ?
        SQL;

        $columns = [
            'a' => new Column('alpha'),
            'b' => new Column('beta'),
            'c' => new Column('gamma'),
            'd' => new Column('delta'),
            'e' => new Column('epsilon'),
        ];
        $modifications = $this->createMock(Modifications\Collection::class);
        $modifications->expects($this->once())
            ->method('count')
            ->willReturn(3);
        $modifications->expects($this->any())
            ->method('getNames')
            ->willReturn(new Text(['a', 'c', 'e']));
        $unit = MySqlGenerator::getInstance();
        $meta = new Meta(
            new Table('greek', 'id'),
            new Columns\Collection($columns),
            null,
            null
        );

        $actual = $unit->getUpdateQuery($meta, $modifications);

        $this->assertEquals($expected, $actual);
    }

    public function testGetDeleteQuery(): void
    {
        $expected = <<<SQL
        DELETE FROM `table`
        WHERE `id` = ?
        SQL;

        $meta = new Meta(
            new Table('table', 'id'),
            new Columns\Collection([]),
            null,
            null
        );

        $unit   = MySqlGenerator::getInstance();
        $actual = $unit->getDeleteQuery($meta);

        $this->assertEquals($expected, $actual);
    }

    public function testGetDeleteQueryWithMultipleRows(): void
    {
        $expected = <<<SQL
        DELETE FROM `table`
        WHERE `id` IN (?,?,?,?,?)
        SQL;

        $meta = new Meta(
            new Table('table', 'id'),
            new Columns\Collection([]),
            null,
            null
        );

        $unit   = MySqlGenerator::getInstance();
        $actual = $unit->getDeleteQuery($meta, 5);

        $this->assertEquals($expected, $actual);

    }
}
