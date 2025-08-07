<?php

namespace Subtext\Persistables\Databases;

use PDO;
use TypeError;

interface Connection
{
    /**
     * Singleton creator
     *
     * @return self
     */
    public static function getInstance(): self;

    /**
     * @return PDO
     * @throws TypeError if PDO is null
     */
    public function getPdo(): PDO;
}
