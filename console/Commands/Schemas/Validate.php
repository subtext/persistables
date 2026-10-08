<?php

namespace Subtext\Persistables\Console\Commands\Schemas;

use InvalidArgumentException;
use PDO;
use PDOException;
use Subtext\Persistables\Console\Schemas\Attributes;
use Subtext\Persistables\Console\Schemas\Databases;
use Subtext\Persistables\Databases\Connection;
use Subtext\Persistables\Databases\Sql;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Validate extends Command
{
    private string $rootPath;

    public function __construct(string $root)
    {
        $this->rootPath = $root;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('schema:validate')
            ->setDescription(
                'Validate the current persistables schema against the database'
            )->addOption(
                'path',
                'p',
                InputOption::VALUE_REQUIRED,
                'Directory where the persistables classes are located'
            )->addOption(
                'namespace',
                'ns',
                INPUTOption::VALUE_REQUIRED,
                'Namespace of the persistables classes'
            )->addOption(
                'dbdriver',
                null,
                InputOption::VALUE_REQUIRED,
                'Database driver',
                'mysql'
            )->addOption(
                'dbhost',
                null,
                InputOption::VALUE_REQUIRED,
                'Database host',
                getenv('DB_HOST') ?? 'sql'
            )->addOption(
                'dbname',
                null,
                InputOption::VALUE_REQUIRED,
                'Database name',
                getenv('DB_NAME') ?? 'persistables'
            )->addOption(
                'dbuser',
                null,
                InputOption::VALUE_REQUIRED,
                'Database username',
                getenv('DB_USER') ?? 'root'
            )->addOption(
                'dbpass',
                null,
                InputOption::VALUE_REQUIRED,
                'Database password',
                getenv('DB_PASSWORD') ?? 'password'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = Command::FAILURE;
        if (!file_exists($this->rootPath)) {
            throw new InvalidArgumentException(sprintf(
                'The root directory "%s" does not exist',
                $this->rootPath
            ));
        }
        $path = '/src';
        if ($input->hasOption('path')) {
            $path = '/' . $input->getOption('path');
        }
        if ($input->hasOption('namespace')) {

        }
        $attributes = new Attributes\Reader($this->rootPath);
        $meta       = $attributes->getMetaCollection($this->rootPath . $path);
        $connection = new Connection(
            $input->getOption('dbname'),
            $input->getOption('dbhost'),
            $input->getOption('dbuser'),
            $input->getOption('dbpass'),
            $input->getOption('dbdriver'),
            'utf8mb4',
            function ($name, $host, $user, $pass, $driver, $charset): ?PDO {
                try {
                    $pdo = new PDO(
                        sprintf('mysql:host=%s;port=3306;dbname=%s', $host, $name),
                        $user,
                        $pass,
                        $charset
                    );
                } catch (PDOException $e) {
                    $pdo = null;
                } finally {
                    return $pdo;
                }
            }
        );
        $database = Sql::getInstance($connection);
        return $result;
    }
}
