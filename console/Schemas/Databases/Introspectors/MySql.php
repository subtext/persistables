<?php

namespace Subtext\Persistables\Console\Schemas\Databases\Introspectors;

use PDO;
use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Columns;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Databases\Meta;
use Subtext\Persistables\Databases\Sql;
use Throwable;

class MySql implements Introspector
{
    protected Sql $db;

    public function __construct(Sql $db)
    {
        $this->db = $db;
    }

    public function getMeta(Meta $runtime): Meta
    {
        try {
            $meta = new Meta(
                $this->getTable($runtime),
                $this->getColumns($runtime),
                $this->getJoins($runtime),
                $this->getPersistables($runtime),
            );
        } catch (Throwable $e) {
            // record failures
        } finally {
            return $meta;
        }
    }

    private function getTable(Meta $runtime): ?Table
    {
        $table = $this->db->getQueryResult(
            <<<SQL
            SELECT TABLE_NAME AS name
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND TABLE_TYPE = 'BASE TABLE'
            SQL,
            [$runtime->getTable()->name]
        );
        $pk = $this->db->getQueryResult(
            $this->db->getPrimaryKeyQuery($table)
        );
        return new Table($table, $pk);
    }

    /**
     * Reads column definitions and index membership for the runtime table.
     *
     * Getter and setter names are left unset. Nullable columns with a null
     * default are marked with nullDefault because MySQL does not distinguish
     * an omitted default from an explicit DEFAULT NULL here.
     *
     * @throws \UnexpectedValueException If a column type is unsupported or a
     *     column belongs to multiple unique indexes or multiple non-unique indexes.
     * @throws \PDOException If the schema query fails.
     */
    protected function getColumns(Meta $runtime): Columns\Collection
    {
        $data = $this->db->getQueryData(
            <<<SQL
            SELECT c.COLUMN_NAME AS name,
                   c.TABLE_NAME AS `table`,
                   c.DATA_TYPE AS data_type,
                   c.COLUMN_TYPE AS column_type,
                   (c.COLUMN_KEY = 'PRI') AS `primary`,
                   (c.GENERATION_EXPRESSION <> '') AS `readonly`,
                   c.CHARACTER_MAXIMUM_LENGTH AS `limit`,
                   (c.IS_NULLABLE = 'YES') AS nullable,
                   (c.EXTRA LIKE '%auto_increment%') AS increment,
                   c.NUMERIC_PRECISION AS `precision`,
                   c.NUMERIC_SCALE AS scale,
                   (c.COLUMN_TYPE REGEXP '(^| )unsigned( |$)') AS `unsigned`,
                   c.COLUMN_DEFAULT AS `default`,
                   (c.COLUMN_DEFAULT IS NULL AND c.IS_NULLABLE = 'YES') AS nullDefault,
                   s.INDEX_NAME AS index_name,
                   s.NON_UNIQUE AS non_unique,
                   s.SEQ_IN_INDEX AS index_position,
                   s.SUB_PART AS index_prefix,
                   s.INDEX_TYPE AS index_type,
                   i.column_count AS index_column_count
            FROM information_schema.COLUMNS AS c
            LEFT JOIN information_schema.STATISTICS AS s
              ON s.TABLE_SCHEMA = c.TABLE_SCHEMA
             AND s.TABLE_NAME = c.TABLE_NAME
             AND s.COLUMN_NAME = c.COLUMN_NAME
            LEFT JOIN (
                SELECT TABLE_SCHEMA, TABLE_NAME, INDEX_NAME, COUNT(*) AS column_count
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :name
                GROUP BY TABLE_SCHEMA, TABLE_NAME, INDEX_NAME
            ) AS i
              ON i.TABLE_SCHEMA = s.TABLE_SCHEMA
             AND i.TABLE_NAME = s.TABLE_NAME
             AND i.INDEX_NAME = s.INDEX_NAME
            WHERE c.TABLE_SCHEMA = DATABASE()
              AND c.TABLE_NAME = :name
            ORDER BY c.ORDINAL_POSITION, s.INDEX_NAME
            SQL,
            [':name' => $runtime->getTable()->name]
        );
        $columns = new Columns\Collection();
        // An index join can return several rows for the same column.
        $definitions = [];
        foreach ($data as $column) {
            if (!isset($definitions[$column->name])) {
                $type = match ($column->data_type) {
                    'tinyint' => preg_match('/^tinyint\\(1\\)/i', $column->column_type)
                        ? Columns\Type::BOOL : Columns\Type::INTEGER,
                    'smallint', 'mediumint', 'int', 'integer' => Columns\Type::INTEGER,
                    'bigint' => Columns\Type::LONG,
                    'decimal', 'numeric' => Columns\Type::DECIMAL,
                    'float', 'double', 'real' => Columns\Type::FLOAT,
                    'char' => Columns\Type::CHARACTER,
                    'varchar' => Columns\Type::STRING,
                    'tinytext', 'text', 'mediumtext', 'longtext' => Columns\Type::TEXT,
                    'date' => Columns\Type::DATE,
                    'datetime', 'timestamp' => Columns\Type::DATETIME,
                    'json' => Columns\Type::JSON,
                    default => throw new \UnexpectedValueException(sprintf(
                        'Unsupported MySQL type %s for column %s.',
                        $column->column_type,
                        $column->name,
                    )),
                };
                // Getters and setters are PHP metadata, not database metadata.
                // COLUMNS cannot distinguish DEFAULT NULL from an omitted default.
                $definitions[$column->name] = new Column(
                    name: $column->name,
                    table: $column->table,
                    primary: (bool) $column->primary,
                    readonly: (bool) $column->readonly,
                    type: $type,
                    limit: $column->limit === null ? 255 : (int) $column->limit,
                    nullable: (bool) $column->nullable,
                    increment: (bool) $column->increment,
                    precision: $column->precision === null ? null : (int) $column->precision,
                    scale: $column->scale === null ? null : (int) $column->scale,
                    unique: false,
                    index: false,
                    unsigned: (bool) $column->unsigned,
                    nullDefault: (bool) $column->nullDefault,
                    default: $column->default,
                );
            }
            if ($column->index_name === null || $column->index_name === 'PRIMARY') {
                continue;
            }
            $definition = $definitions[$column->name];
            $unique = !(bool) $column->non_unique;
            $flag = $unique ? 'unique' : 'index';
            $group = $unique ? 'uqGroup' : 'idxGroup';
            if ($definition->$flag) {
                throw new \UnexpectedValueException(sprintf(
                    'Column %s belongs to multiple %s indexes; Column cannot represent them.',
                    $column->name,
                    $flag,
                ));
            }
            $definition->$flag = true;
            $definition->$group = (int) $column->index_column_count > 1
                ? $column->index_name : null;
        }
        foreach ($definitions as $name => $column) {
            $columns->set($name, $column);
        }
        return $columns;
    }

    private function getJoins(Meta $runtime)
    {
    }

    private function getPersistables(Meta $runtime)
    {
    }
}
