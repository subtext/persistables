<?php

namespace Subtext\Persistables\Databases;

use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use stdClass;
use Subtext\Collections\Text;
use Subtext\Persistables\Persistable;
use Subtext\Persistables\Queries\Collection;
use Throwable;

class Sql
{
    public const int RESULT_FETCH_ALL = 1;
    public const int RESULT_FETCH_ONE = 2;
    public const int RESULT_FETCH_ROW = 3;
    public const int RESULT_FETCH_COL = 4;
    public const string SQL_SELECT    = 'SELECT %s FROM %s WHERE %s';
    public const string SQL_INSERT    = 'INSERT INTO `%s` (%s) VALUES XXX';
    public const string SQL_UPDATE    = 'UPDATE `%s` SET %s WHERE `%s` = ?';
    public const string SQL_DELETE    = 'DELETE FROM `%s` WHERE `%s` IN(XXX)';
    public const array SQL_JOINS      = ['INNER','LEFT','RIGHT','FULL OUTER','JOIN'];
    private static self $instance;
    private readonly PDO $pdo;
    private array $statements = [];
    private array $errors     = [];

    private function __construct(Connection $connection)
    {
        $this->pdo = $connection->getPdo();
    }

    /**
     * Returns a singleton instance of the Sql Database, or a new instance if
     * specifically requested.
     *
     * @param Connection|null $connection
     * @param bool $new
     *
     * @return self
     * @throws InvalidArgumentException
     */
    public static function getInstance(
        ?Connection $connection,
        bool $new = false
    ): self {
        if (!isset(self::$instance) || $new) {
            if ($connection === null) {
                throw new InvalidArgumentException(
                    'Connection must be set before running Sql'
                );
            }
            self::$instance = new self($connection);
        }
        return self::$instance;
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
     * Format an insert query with multiple rows of insert data. The params data
     * is modified to be a single array of all the data to be inserted, so that
     * it can be bound to the prepared statement.
     *
     * @param string $query  The sql query to be formatted
     * @param array  $params The data to be inserted, must be an array of arrays
     * @param string $search The string to be replaced in the query
     *
     * @return string The pdo formatted query with correct number of bindings
     */
    public static function formatInsert(
        string $query,
        array &$params,
        string $search = 'XXX'
    ): string {
        $original = $params;
        $params   = [];
        $slugs    = [];
        $replace  = '';
        foreach ($original as $row) {
            array_push($params, ...$row);
            array_push($slugs, '(' . str_repeat('?,', count($row) - 1) . '?)');
        }
        $replace .= implode(',', $slugs);
        return str_replace($search, $replace, $query);
    }

    /**
     * Generate a basic select using the primary key of an entity.
     *
     * @param Meta $meta
     *
     * @return string
     */
    public static function getSelectQuery(Meta $meta): string
    {
        $table  = $meta->getTable();
        $cols   = $meta->getColumns();
        $fields = new Text();
        foreach ($cols as $property => $column) {
            if ($property === $column->name) {
                $fields->append(sprintf('`%s`', $column->name));
            } else {
                $fields->append(sprintf('`%s` AS `%s`', $column->name, $property));
            }
        }
        return sprintf(
            self::SQL_SELECT,
            $fields->concat(),
            $table->name,
            sprintf('`%s` = ?', $table->primaryKey)
        );
    }

    /**
     * Generate a basic SQL insert statement based on the data provided.
     *
     * @param array $data
     * @param string $table
     *
     * @return string
     */
    public static function getInsertQuery(array $data, string $table): string
    {
        return sprintf(self::SQL_INSERT, $table, implode(',', array_keys($data)));
    }

    /**
     * Generate a basic SQL update statement, using only the columns which have
     * been modified.
     *
     * @param array $data
     * @param string $table
     * @param string $primary
     *
     * @return string
     */
    public static function getUpdateQuery(
        array $data,
        string $table,
        string $primary
    ): string {
        $str = '';
        foreach (array_keys($data) as $key) {
            $str .= sprintf('`%s` = ?,', $key);
        }
        return sprintf(self::SQL_UPDATE, $table, rtrim($str, ','), $primary);
    }

    /**
     * Generate a basic SQL delete statement using the primary key for the entity.
     *
     * @param string $table
     * @param string $primaryKey
     *
     * @return string
     */
    public static function getDeleteQuery(string $table, string $primaryKey): string
    {
        return sprintf(self::SQL_DELETE, $table, $primaryKey);
    }

    /**
     * The $class parameter is used with the PDO::FETCH_CLASS fetch style. If
     * the class is not found, the fetch style will default to PDO::FETCH_OBJ.
     *
     * @param string      $sql   The SQL query to be passed to the database
     * @param array       $data  An array of data to be bound to the prepared query
     * @param int         $style An integer indicating the PDO fetch style
     * @param string|null $class The class to be used for fetching objects
     *
     * @return array An array of objects or arrays of database query results
     */
    public function getQueryData(
        string $sql,
        array $data = [],
        int $style = PDO::FETCH_OBJ,
        ?string $class = null
    ): array {
        return $this->getResultFromDatabase(
            $sql,
            $data,
            self::RESULT_FETCH_ALL,
            $style,
            $class
        );
    }

    /**
     * Get a single value from the database query. The value is cast to an
     * integer or float if it is numeric.
     *
     * @param string $sql  The SQL query to be passed to the database
     * @param array  $data An array of data to be bound to the prepared query
     *
     * @return int|float|string The value of the query result
     */
    public function getQueryResult(string $sql, array $data = []): int|float|string
    {
        $value = $this->getResultFromDatabase(
            $sql,
            $data,
            self::RESULT_FETCH_ONE,
            PDO::FETCH_NUM
        );
        if (is_numeric($value)) {
            if (str_contains($value, '.')) {
                $result = (float) $value;
            } else {
                $result = (int) $value;
            }
        } else {
            $result = ($value ?? '');
        }
        return $result;
    }

    /**
     * Get a single row from the database query. The row is returned as an array
     * or object depending on the fetch style.
     *
     * @param string      $sql   The SQL query to be passed to the database
     * @param array       $data  An array of data to be bound to the prepared query
     * @param int         $style An integer indicating the PDO fetch style
     * @param string|null $class The class to be used for fetching objects
     *
     * @return array|Persistable|stdClass The row of the query result
     */
    public function getQueryRow(
        string $sql,
        array $data = [],
        int $style = PDO::FETCH_NUM,
        ?string $class = null
    ): array|Persistable|stdClass {
        return $this->getResultFromDatabase(
            $sql,
            $data,
            self::RESULT_FETCH_ROW,
            $style,
            $class
        );
    }

    /**
     * Get a single column from the database query. The column is returned as an
     * array of values.
     *
     * @param string $sql  The SQL query to be passed to the database
     * @param array  $data An array of data to be bound to the prepared query
     *
     * @return array An array of values from the query result
     */
    public function getQueryColumn(string $sql, array $data = []): array
    {
        return $this->getResultFromDatabase(
            $sql,
            $data,
            self::RESULT_FETCH_COL,
            PDO::FETCH_NUM
        );
    }

    /**
     * Get the last inserted id from the database query. The id is cast to an
     * integer.
     *
     * @param string $sql  The SQL query to be passed to the database
     * @param array  $data An array of data to be bound to the prepared query
     *
     * @return int The id of the last inserted row
     */
    public function getIdForInsert(string $sql, array $data = []): int
    {
        if (!str_starts_with(trim($sql), 'INSERT')) {
            throw new InvalidArgumentException(
                "SQL statement must begin with INSERT"
            );
        }
        $stmt = $this->getPreparedStatement($sql);
        if ($this->executeStatement($stmt, $data) === false) {
            $id = 0;
        } else {
            $id = (int) $this->pdo->lastInsertId();
        }

        return $id;
    }

    /**
     * Get the number of rows affected by the database query. The count is cast
     * to an integer.
     *
     * @param string $sql  The SQL query to be passed to the database
     * @param array  $data An array of data to be bound to the prepared query
     *
     * @return int The number of rows affected by the query
     */
    public function getNumRowsAffectedForUpdate(string $sql, array $data = []): int
    {
        if (!str_starts_with(trim($sql), 'UPDATE')) {
            throw new InvalidArgumentException(
                "SQL statement must begin with UPDATE"
            );
        }
        try {
            $stmt = $this->getPreparedStatement($sql);
            if ($this->executeStatement($stmt, $data) === false) {
                $count = 0;
            } else {
                $count = $stmt->rowCount();
            }
        } catch (PDOException $e) {
            $this->recordError($e);
            $count = 0;
        } finally { //@codeCoverageIgnore
            return $count;
        }
    }

    /**
     * @codeCoverageIgnore
     * Get the number of rows affected by the database query. The count is cast
     * to an integer.
     *
     * @param string $sql  The SQL query to be passed to the database
     * @param array  $data An array of data to be bound to the prepared query
     *
     * @return iterable An iterable object or array of database query results
     */
    public function getBigQuery(
        string $sql,
        array $data = [],
        int $style = PDO::FETCH_OBJ
    ): iterable {
        try {
            $stmt = $this->getPreparedStatement($sql);
            if (!$this->executeStatement($stmt, $data) === false) {
                while ($record = $stmt->fetch($style)) {
                    yield $record;
                }
            }
        } catch (PDOException $e) {
            $this->recordError($e);
        }
    }

    /**
     * Execute a database query and return a boolean indicating success or
     * failure.
     *
     * @param string $sql  The SQL query to be passed to the database
     * @param array  $data An array of data to be bound to the prepared query
     *
     * @return bool A boolean indicating the success of the query
     */
    public function execute(string $sql, array $data = []): bool
    {
        try {
            $stmt   = $this->getPreparedStatement($sql);
            $result = $this->executeStatement($stmt, $data);
        } catch (PDOException $e) {
            $this->recordError($e);
            $result = false;
        } finally { //@codeCoverageIgnore
            return $result;
        }
    }

    /**
     * Execute a transaction of database commands. The transaction will be
     * committed if all commands are successful, otherwise it will be rolled
     * back.
     *
     * @param Collection $commands A Collection of SqlCommands to be executed
     *
     * @return void
     */
    public function executeTransaction(Collection $commands): void
    {
        $success = true;
        foreach ($commands as $command) {
            $this->pdo->beginTransaction();
            try {
                $stmt    = $this->getPreparedStatement($command->getQuery());
                $success = $this->executeStatement($stmt, $command->getData());
            } catch (Throwable $e) {
                $success = false;
            }
            if ($success) {
                try {
                    $this->pdo->commit();
                } catch (Throwable $e) {
                    $this->pdo->rollback();
                }
            } else {
                $this->pdo->rollback();
            }
        }
    }

    /**
     * Quote a string for use in a database query. The quoted string is returned
     * or null if the string could not be quoted.
     *
     * @param string $value The string to be quoted
     *
     * @return string|null The quoted string or null
     */
    public function quote(string $value): ?string
    {
        $quoted = $this->pdo->quote($value);
        if ($quoted === false) {
            $quoted = null;
        }
        return $quoted;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get a prepared statement from the database connection. The statement is
     * stored in an array for reuse.
     *
     * @param string $sql The SQL query to be prepared
     *
     * @return PDOStatement The prepared statement
     */
    private function getPreparedStatement(string $sql): PDOStatement
    {
        $sql  = trim($sql);
        $hash = md5($sql);
        if (empty($this->statements[$hash])) {
            if (($stmt = $this->pdo->prepare($sql)) === false) {
                throw new PDOException(
                    "The query with hash: $hash could not be prepared"
                );
            }
            $this->statements[$hash] = $stmt;
        }

        return $this->statements[$hash];
    }

    /**
     * Query the database given the query data and fetch styles
     *
     * @param string $sql   The SQL query to be passed to the database
     * @param array  $data  An array of data to be bound to the prepared query
     * @param int    $type  An integer indicating how you want results returned
     * @param int    $style An integer indicating the PDO fetch style
     *
     * @return string|array
     */
    private function getResultFromDatabase(
        string $sql,
        array $data,
        int $type,
        int $style,
        ?string $class = null
    ): array|string|null|Persistable|stdClass {
        try {
            $stmt = $this->getPreparedStatement($sql);
            $this->executeStatement($stmt, $data);
            switch ($type) {
                case self::RESULT_FETCH_ALL:
                    if ($class) {
                        $result = $stmt->fetchAll($style, $class);
                    } else {
                        $result = $stmt->fetchAll($style);
                    }
                    break;
                case self::RESULT_FETCH_ONE:
                    $result = $stmt->fetchColumn();
                    break;
                case self::RESULT_FETCH_ROW:
                    if ($class) {
                        $results = $stmt->fetchAll($style, $class);
                        $result  = current($results);
                    } else {
                        $result = $stmt->fetch($style);
                    }
                    if ($result === false) {
                        $result = [];
                    }
                    break;
                case self::RESULT_FETCH_COL:
                    $result = [];
                    while (($data = $stmt->fetchColumn()) !== false) {
                        $result[] = $data;
                    }
                    break;
            }
            if ($result === false) {
                $result = $this->getDefaultResult($type);
            }
        } catch (PDOException $e) {
            $result = $this->getDefaultResult($type);
            $this->recordError($e);
        } catch (Throwable $e) {
            $result = $this->getDefaultResult($type);
            $this->recordError($e);
        } finally { // @codeCoverageIgnore
            return $result;
        }
    }

    /**
     * Get the default result for a query type
     *
     * @param int $type The type of query result
     *
     * @return string|array
     */
    private function getDefaultResult(int $type)
    {
        if ($type === self::RESULT_FETCH_ONE) {
            $result = '';
        } else {
            $result = [];
        }

        return $result;
    }

    /**
     * Execute a prepared statement with bound parameters
     *
     * @param PDOStatement $stmt   The prepared statement to be executed
     * @param array        $params The data to be bound to the prepared query
     *
     * @return bool A boolean indicating the success of the query
     */
    private function executeStatement(PDOStatement $stmt, array $params): bool
    {
        try {
            $success = true;
            if ($this->hasTypeData($params)) {
                foreach ($params as $key => $obj) {
                    if (is_object($obj)) {
                        $stmt->bindValue($key, $obj->value, $obj->type);
                    } else {
                        $stmt->bindValue($key, $obj);
                    }
                }
                $result = $stmt->execute();
            } else {
                $result = $stmt->execute($params);
            }
            if ($result === false) {
                $success   = false;
                $errorInfo = $stmt->errorInfo();
                array_push($this->errors, [
                    'msg'  => $errorInfo[2],
                    'code' => $errorInfo[0],
                    'info' => $errorInfo[1],
                ]);
                if (!defined('TEST_MODE')) {
                    error_log('PDO_FAILED_QUERY ' . $errorInfo[2]);
                    error_log('PDO_FAILED_QUERY ' . $_SERVER['SCRIPT_FILENAME']);
                    error_log('PDO_FAILED_QUERY ' . $stmt->queryString);
                }
            }
        } catch (PDOException $e) {
            $success = false;
            $this->recordError($e);
        } finally { //@codeCoverageIgnore
            return $success;
        }
    }

    /**
     * Check if the data array contains type data
     *
     * @param array $params The data to be bound to the prepared query
     *
     * @return bool A boolean indicating if the data array contains type data
     */
    private function hasTypeData(array $params): bool
    {
        $value = false;
        foreach ($params as $param) {
            if (is_object($param)) {
                $value = true;
                break;
            }
        }
        return $value;
    }

    /**
     * @param Throwable $e
     *
     * @return void
     */
    private function recordError(Throwable $e): void
    {
        array_push($this->errors, [
            'msg'  => $e->getMessage(),
            'code' => $e->getCode(),
            'info' => $e->getTraceAsString(),
        ]);
    }
}
