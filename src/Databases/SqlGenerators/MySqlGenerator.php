<?php

namespace Subtext\Persistables\Databases\SqlGenerators;

use Subtext\Collections\Text;
use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Joins\Collection;
use Subtext\Persistables\Databases\Meta;
use Subtext\Persistables\Databases\SqlGenerator;
use Subtext\Persistables\Modifications;

class MySqlGenerator implements SqlGenerator
{
    private static ?self $instance = null;
    public const string SQL_SELECT = 'SELECT %s FROM %s WHERE %s';
    public const string SQL_INSERT = 'INSERT INTO `%s` (%s) VALUES XXX';
    public const string SQL_UPDATE = 'UPDATE `%s` SET %s WHERE `%s` = ?';
    public const string SQL_DELETE = 'DELETE FROM `%s` WHERE `%s` ';
    public const string SQL_COLUMN = '`%s`.`%s`';

    private function __construct()
    {
    }

    /**
     * Returns singleton instance of the generator.
     *
     * @return SqlGenerator
     */
    public static function getInstance(): SqlGenerator
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getSelectQuery(Meta $meta, ?string $clause = null): string
    {
        $table   = $meta->getTable();
        $columns = $meta->getColumns();
        $joins   = $meta->getJoins();
        $fields  = new Text();
        foreach ($columns as $property => $column) {
            $tableName = $column->table ?? $table->name;
            if (($property === $column->name) || is_null($column->name)) {
                $fields->append(
                    $this->formatColumnName($tableName, $column->name)
                );
            } else {
                $fields->append($this->formatColumnName(
                    $tableName,
                    $column->name,
                    $property
                ));
            }
        }
        return sprintf(
            self::SQL_SELECT,
            $fields->concat(",\n"),
            sprintf(
                "`%s`\n%s",
                $table->name,
                $joins ? $this->getJoinClauses($joins, $table->name)->concat("\n") : ''
            ),
            sprintf('`%s` = ?', $table->primaryKey)
        );
    }

    public function getInsertQuery(Meta $meta, int $rows = 1): string
    {
        $table   = $meta->getTable();
        $columns = $meta->getColumns()->filter(function (Column $column) {
            return $column->readonly === false;
        });
        return $this->formatInsert(
            sprintf(
                self::SQL_INSERT,
                $table->name,
                trim(implode(', ', $columns->getKeys()))
            ),
            $rows,
            $columns->count()
        ) . sprintf(
            ' ON DUPLICATE KEY UPDATE %s = LAST_INSERT_ID(%s)',
            $table->primaryKey,
            $table->primaryKey
        );
    }

    public function getUpdateQuery(
        Meta $meta,
        Modifications\Collection $modifications
    ): string {
        $sql     = '';
        $fields  = [];
        $table   = $meta->getTable();
        $columns = $meta->getColumns();
        if ($modifications->count()) {
            foreach ($modifications->getNames() as $property) {
                $column = $columns->get($property);
                if (!$column->readonly && is_null($column->table)) {
                    $fields[] = $this->formatColumnName(
                        $table->name,
                        $column->name,
                        param: true
                    );
                }
            }
            $sql = sprintf(
                self::SQL_UPDATE,
                $table->name,
                implode(",\n", $fields),
                $table->primaryKey
            );
        }
        return $sql;
    }

    public function getDeleteQuery(Meta $meta, int $count = 1): string
    {
        $table = $meta->getTable();
        $query = sprintf(self::SQL_DELETE, $table->name, $table->primaryKey);
        if ($count === 1) {
            $query .= '= ?';
        } else {
            $query .= 'IN (XXX)';
        }
        return $this->formatIn($query, $count);
    }

    /**
     * Format a snippet of SQL code to perform a JOIN operation.
     *
     * @param Collection $joins The collection of join attributes to be applied
     * @param string $tableName The name of the table to join against
     *
     * @return Text
     */
    private function getJoinClauses(
        Collection $joins,
        string $tableName
    ): Text {
        $clauses = new Text();
        foreach ($joins as $join) {
            $origin  = $this->formatColumnName($tableName, $join->key);
            $foreign = $this->formatColumnName($join->table, $join->key);
            if ($join->foreign) {
                $foreign = $this->formatColumnName($join->table, $join->foreign);
            }
            $clauses->append(trim(sprintf(
                '%s JOIN `%s` ON %s',
                $join->type == 'JOIN' ? '' : $join->type,
                $join->table,
                sprintf('%s = %s', $origin, $foreign)
            )));
        }
        return $clauses;
    }

    /**
     * Format an insert query with multiple rows of insert data. The params data
     * is modified to be a single array of all the data to be inserted, so that
     * it can be bound to the prepared statement.
     *
     * @param string $query The sql query to be formatted
     * @param int $rows
     * @param int $columns
     * @param string $search The string to be replaced in the query
     *
     * @return string The pdo formatted query with correct number of bindings
     */
    private function formatInsert(
        string $query,
        int $rows,
        int $columns,
        string $search = 'XXX'
    ): string {
        $slugs   = [];
        $replace = '';
        while ($rows--) {
            $slugs[] = '(' . str_repeat('?,', $columns - 1) . '?)';
        }
        $replace .= implode(',', $slugs);
        return str_replace($search, $replace, $query);
    }

    /**
     * Format a query with multiple placeholders for data to be inserted. The
     * number of placeholders is determined by the count parameter.
     *
     * @param string $query  The sql query to be formatted
     * @param int    $count  The number of placeholders to be inserted
     * @param string $search The string to be replaced in the query
     *
     * @return string The pdo formatted query with correct number of bindings
     */
    public static function formatIn(
        string $query,
        int $count,
        string $search = 'XXX'
    ): string {
        $replace = str_repeat('?,', $count - 1) . '?';
        return str_replace($search, $replace, $query);
    }

    /**
     * Format a snippet of SQL code explicitly setting table and column, with
     * optional arguments for aliasing and parameter comparison
     *
     * @param string $table      The string value table name
     * @param string $column     The string value column name
     * @param string|null $alias A non-null value is used as the SQL AS alias
     * @param bool $param        If true, appends a parameter token for binding
     *
     * @return string
     */
    private function formatColumnName(
        string $table,
        string $column,
        ?string $alias = null,
        bool $param = false
    ): string {
        $field = sprintf(self::SQL_COLUMN, $table, $column);
        if ($alias) {
            $field = sprintf('%s AS `%s`', $field, $alias);
        } elseif ($param) {
            $field .= ' = ?';
        }
        return $field;
    }
}
