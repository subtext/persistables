<?php

namespace Subtext\Persistables\Databases;

use Closure;
use PDO;
use Subtext\Persistables\Databases\SqlGenerators\MySqlGenerator;

class Connection
{
    /**
     * A container for configuring the PDO object. Pass in all of the parameters
     * and use them as arguments for a callable
     * @param string $database The database name.
     * @param string $hostname The database host url.
     * @param string $username The database username.
     * @param string $password The database password.
     * @param string $driver   A compatible PDO driver string name.
     * @param string $charset  The character set to pass to the PDO object.
     * @param Closure $fn      A function which accepts each of the previous
     *                         arguments and returns a PDO object.
     */
    public function __construct(
        private readonly string $database,
        private readonly string $hostname,
        private readonly string $username,
        private readonly string $password,
        private readonly string $driver,
        private readonly string $charset,
        private readonly Closure $fn
    ) {}

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

    /**
     * Currently only mysql is supported. Other database drivers will be added
     * in the future.
     *
     * @return SqlGenerator
     */
    public function getSqlGenerator(): SqlGenerator
    {
        return MySqlGenerator::getInstance();
    }
}
