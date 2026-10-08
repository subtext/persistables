<?php

namespace Subtext\Persistables\Console\Schemas\Databases\Introspectors;

use InvalidArgumentException;
use Subtext\Persistables\Console\Schemas\Databases\Introspector;
use Subtext\Persistables\Databases\Sql;

class Factory
{
    public static function getInstance(Sql $db): Introspector
    {
        switch ($db->getDriver()) {
            case 'mysql':
                $introspector = new MySql($db);
                break;
            default:
                throw new InvalidArgumentException(sprintf(
                    'The driver "%s" is not supported.',
                    $db->getDriver()
                ));
        }
        return $introspector;
    }
}
