<?php

namespace Subtext\Persistables\Console\Schemas\Databases\Introspectors;

use Subtext\Persistables\Databases\Meta;

interface Introspector
{
    public function getMeta(Meta $runtime): Meta;
}
