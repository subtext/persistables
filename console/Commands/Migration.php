<?php

namespace Subtext\Persistables\Console\Commands;

use http\Exception\InvalidArgumentException;
use Subtext\Persistables\Console\Arguments\MigrationAction;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Migration extends Command
{
    protected static $defaultName        = 'db:migration';
    protected static $defaultDescription = 'Database migration and schema management';

    protected function configure(): void
    {
        $this->setName(self::$defaultName);
        $this->setDescription(self::$defaultDescription);
        $this->addArgument(
            'action',
            InputArgument::REQUIRED,
            'A migration command to execute',
            MigrationAction::LIST->value,
            $this->getActionCommands()
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = Command::SUCCESS;
        try {
            $command = $input->getArgument('action');
            switch ($command) {
                case MigrationAction::LIST:
                    // list the migrations
                    break;
                case MigrationAction::MIGRATE:
                    // migrate any new schema
                    break;
                case MigrationAction::ROLLBACK:
                    // rollback a specific schema change
                    break;
                default:
                    throw new InvalidArgumentException(sprintf(
                        'action must be one of: %s',
                        implode(', ', $this->getActionCommands())
                    ));
            }
        } catch (Throwable $e) {
            $result = Command::FAILURE;
        } finally {
            return $result;
        }
    }

    private function getActionCommands(): array
    {
        return array_column(MigrationAction::cases(), 'value');
    }
}
