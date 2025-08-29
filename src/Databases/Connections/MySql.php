<?php

namespace Subtext\Persistables\Databases\Connections;

use PDO;
use PDOException;
use RuntimeException;
use Subtext\Persistables\Databases\Connection;
use Subtext\Persistables\Databases\SqlGenerator;
use Subtext\Persistables\Databases\SqlGenerators\MySqlGenerator;

class MySql implements Connection
{
    private static ?self $instance = null;
    private ?PDO $pdo;
    private SqlGenerator $generator;

    private function __construct(?PDO $pdo = null)
    {
        $this->pdo       = $pdo;
        $this->generator = MySqlGenerator::getInstance();
    }

    /**
     * Database credentials must be applied as environment variables.
     *
     * @param Auth|null $auth
     * @param bool $new
     * @return self
     */
    public static function getInstance(?Auth $auth = null, bool $new = false): self
    {
        try {
            if (self::$instance === null || $new) {
                if ($auth === null) {
                    $name = getenv('DB_NAME');
                    $host = getenv('DB_HOST');
                    $user = getenv('DB_USER');
                    $pass = getenv('DB_PASS');
                    $char = 'utf8mb4';
                } else {
                    $name = $auth->database;
                    $host = $auth->hostname;
                    $user = $auth->username;
                    $pass = $auth->password;
                    $char = $auth->charset;
                }
                self::$instance = new self(new PDO(
                    "mysql:dbname=$name;host=$host;charset=$char",
                    $user,
                    $pass,
                ));
            }
        } catch (PDOException) {
            self::$instance = new self(null);
        } finally {
            return self::$instance;
        }
    }

    public function getPdo(): PDO
    {
        if ($this->pdo === null) {
            throw new RuntimeException(
                'MySql PDO is not initialized, check credentials.'
            );
        }
        return $this->pdo;
    }

    public function getSqlGenerator(): SqlGenerator
    {
        return $this->generator;
    }
}
