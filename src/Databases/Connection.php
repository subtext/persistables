<?php

namespace Subtext\Persistables\Databases;

use Closure;
use PDO;
use Subtext\Persistables\Databases\SqlGenerators\MySqlGenerator;
use Throwable;

class Connection
{
    /**
     * A container for configuring the PDO object. Pass in all the parameters
     * and use them as arguments for a callable
     *
     * @param string $database The database name.
     * @param string $hostname The database host url.
     * @param string $username The database username.
     * @param string $password The database password.
     * @param string $driver   A compatible PDO driver string name.
     * @param string $charset  The character set to pass to the PDO object.
     * @param Closure(string, string, string, string, string, string): ?PDO $fn
     *     Receives database, hostname, username, password, driver, and charset;
     *     returns a PDO connection or null when the connection fails.
     */
    public function __construct(
        private readonly string $database,
        private readonly string $hostname,
        private readonly string $username,
        private readonly string $password,
        private readonly string $driver,
        private readonly string $charset,
        private readonly Closure $fn
    ) {
    }

    /**
     * Returns a resolver that creates a PDO connection or returns null on failure.
     *
     * @return Closure(string, string, string, string, string, string): ?PDO
     */
    public static function getPdoResolver(): Closure
    {
        return function ($name, $host, $user, $pass, $driver, $charset): ?PDO {
            try {
                $pdo = new PDO(
                    sprintf('%s:host=%s;port=3306;dbname=%s;charset=%s', $driver, $host, $name, $charset),
                    $user,
                    $pass,
                );
            } catch (Throwable $e) {
                $pdo = null;
            } finally {
                return $pdo;
            }
        };
    }

    public function getPdo(): ?PDO
    {
        $pdo = null;
        if ($this->fn) {
            $pdo = ($this->fn)(
                $this->database,
                $this->hostname,
                $this->username,
                $this->password,
                $this->driver,
                $this->charset,
            );
        }
        return $pdo;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Currently, only mysql is supported. Other database drivers will be added
     * in the future.
     *
     * @return SqlGenerator
     */
    public function getSqlGenerator(): SqlGenerator
    {
        return MySqlGenerator::getInstance();
    }
}
