<?php

namespace Subtext\Persistables\Databases\Connections;

use PDO;
use PDOException;
use RuntimeException;
use Subtext\Persistables\Databases\Connection;

class MySql implements Connection
{
    private ?PDO $pdo;
    private static ?self $instance = null;

    private function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    /**
     * Database credentials must be applied as environment variables.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        try {
            if (self::$instance === null) {
                $name           = getenv('DB_NAME');
                $host           = getenv('DB_HOST');
                $user           = getenv('DB_USER');
                $pass           = getenv('DB_PASS');
                self::$instance = new self(new PDO(
                    "mysql:dbname={$name};host={$host};charset=utf8mb4",
                    $user,
                    $pass,
                ));
            }
        } catch (PDOException $e) {
            throw new RuntimeException(
                'The MySql connection could not be established.',
                $e->getCode(),
                $e
            );
        } finally {
            return self::$instance;
        }
    }

    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}
