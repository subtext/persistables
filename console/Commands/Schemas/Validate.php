<?php

namespace Subtext\Persistables\Console\Commands\Schemas;

use InvalidArgumentException;
use Subtext\Persistables\Console\Schemas\Attributes;
use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Columns\Type;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

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
                getenv('DB_PASS') ?? 'password'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = Command::FAILURE;
        if (!file_exists($this->rootPath)) {
            throw new InvalidArgumentException(sprintf(
                'The root directory "%s" does not exist',
                $this->rootPath
            ));
        }
//        $path = '/src';
//        if ($input->hasOption('path')) {
//            $path = '/' . $input->getOption('path');
//        }
//        if ($input->hasOption('namespace')) {
//
//        }
        $reader     = new Attributes\Reader($this->rootPath);
        $meta       = $reader->getMetaCollection();
        $errors     = $reader->getMetaErrors();
        if ($errors->count() > 0) {
            $io->error('There are schema errors for Persistables classes');
            $io->table(
                ['Exception', 'Message'],
                array_map(
                    fn (Throwable $th): array => [get_class($th), $th->getMessage()],
                    $errors->getArrayCopy()
                )
            );
        } else {
            $result = Command::SUCCESS;
            $io->success('The current persistables classes have been validated');
            foreach ($meta as $namespace => $entity) {
                $pk = $entity->getTable()->primaryKey;
                $io->newLine();
                $io->writeLn(sprintf('%s (%s)', $namespace, $entity->getTable()->name));
                $io->table(
                    ['Field', 'Type', 'Null', 'Key', 'Default', 'Extra'],
                    array_map(
                        fn(Column $column): array => [
                            $column->name,
                            $this->getDisplayType($column),
                            $column->nullable && $column->name != $pk ? 'YES' : 'NO',
                            $this->getDisplayKey($column, $entity->getTable()->primaryKey),
                            $column->default !== null ? $column->default : 'NULL',
                            $column->increment || $column->name === $pk ? 'auto_increment' : '',
                        ],
                        $entity->getColumns()->getArrayCopy()
                    )
                );
            }
        }
        return $result;
    }

    private function getDisplayType(Column $column): string
    {
        switch ($column->type) {
            case Type::CHARACTER:
            case TYPE::STRING:
                $string = sprintf('%s(%d)', $column->type->value, $column->limit);
                break;
            case TYPE::DECIMAL:
                $string = sprintf('%s(%d,%d)', $column->type->value, $column->precision, $column->scale);
                break;
            default:
                $string = $column->type->value;
        }
        return $string;
    }

    private function getDisplayKey(Column $column, string $pk): string
    {
        if ($column->name === $pk) {
            $key = 'PRI';
        } elseif (($column->index || $column->unique) && !$column->nullable) {
            $key = 'MUL';
        } else {
            $key = '';
        }
        return $key;
    }
}
