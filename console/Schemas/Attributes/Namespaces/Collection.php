<?php

namespace Subtext\Persistables\Console\Schemas\Attributes\Namespaces;

use Subtext\Collections\Text;
use Symfony\Component\Filesystem\Path;

class Collection extends Text
{
    public function getNamespace(string $filePath): ?string
    {
        $matched  = null;
        $iterator = $this->getIterator();
        $iterator->rewind();
        while ($iterator->valid()) {
            if (Path::isBasePath($iterator->current(), $filePath)) {
                $matched = $iterator->key();
                break;
            }
            $iterator->next();
        }
        return $matched;
    }
}
