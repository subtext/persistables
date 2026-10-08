<?php

namespace Subtext\Persistables\Console\Arguments;

enum MigrationAction: string
{
    case LIST     = 'list';
    case MIGRATE  = 'migrate';
    case ROLLBACK = 'rollback';
}
