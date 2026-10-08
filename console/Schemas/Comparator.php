<?php

namespace Subtext\Persistables\Console\Schemas;

class Comparator
{
    public function diff($schema, $dbSchema): bool
    {
        return true;
    }
}
