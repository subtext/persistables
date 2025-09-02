<?php

namespace Subtext\Persistables\Databases;

use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use stdClass;
use Subtext\Persistables\Modifications;
use Subtext\Persistables\Persistable;
use Subtext\Persistables\Queries\Collection;
use Throwable;

class Sql implements SqlGenerator
{
    public const int RESULT_FETCH_ALL = 1;
    public const int RESULT_FETCH_ONE = 2;
    public const int RESULT_FETCH_ROW = 3;
    public const int RESULT_FETCH_COL = 4;
    private static self $instance;
    private readonly PDO $pdo;
    private readonly SqlGenerator $sqlGenerator;
    private array $statements = [];
    private array $errors     = [];

    private function __construct(Connection $connection)
    {
        $this->pdo          = $connection->getPdo();
        $this->sqlGenerator = $connection->getSqlGenerator();
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

    public function getSelectQuery(Meta $meta, ?string $clause = null): string
    {
        return $this->sqlGenerator->getSelectQuery($meta, $clause);
    }

    public function getInsertQuery(Meta $meta, int $rows = 1): string
    {
        return $this->sqlGenerator->getInsertQuery($meta, $rows);
    }

    public function getUpdateQuery(Meta $meta, Modifications\Collection $modifications): string
    {
        return $this->sqlGenerator->getUpdateQuery($meta, $modifications);
    }

    public function getDeleteQuery(Meta $meta, int $count = 1): string
    {
        return $this->sqlGenerator->getDeleteQuery($meta, $count);
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
        $success = false;
        $this->pdo->beginTransaction();
        foreach ($commands as $command) {
            $stmt    = $this->getPreparedStatement($command->getQuery());
            $success = $this->executeStatement($stmt, $command->getData());
            if (!$success) {
                break;
            }
        }
        if ($success) {
            try {
                $this->pdo->commit();
            } catch (PDOException $e) {
                $this->pdo->rollback();
            }
        } else {
            $this->pdo->rollback();
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
     * @param string $sql The SQL query to be passed to the database
     * @param array $data An array of data to be bound to the prepared query
     * @param int $type An integer indicating how you want results returned
     * @param int $style An integer indicating the PDO fetch style
     * @param string|null $class
     *
     * @return array|string|Persistable|stdClass|null
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
                default:
                    $result = false;
            }
            if (($result ?? false) === false) {
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
    private function getDefaultResult(int $type): string|array
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
            $success = false;
            if ($this->hasTypeData($params)) {
                foreach ($params as $key => $obj) {
                    if (is_object($obj)) {
                        $stmt->bindValue($key, $obj->value, $obj->type);
                    } else {
                        // defaults to string type
                        $stmt->bindValue($key, $obj);
                    }
                }
                $success = $stmt->execute();
            } else {
                $success = $stmt->execute($params);
            }
            if ($success === false) {
                $errorInfo = $stmt->errorInfo();
                if (!empty($errorInfo)) {
                    $this->errors[] = new Error(
                        $errorInfo[2] ?? '',
                        $errorInfo[0] ?? 500,
                        $errorInfo[1] ?? ''
                    );
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
        $this->errors[] = new Error(
            $e->getMessage(),
            $e->getCode(),
            $e->getTraceAsString()
        );
    }
}
