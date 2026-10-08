<?php

namespace Subtext\Persistables\Console\Schemas\Attributes\Namespaces;

use Subtext\Collections\Text;

class Collection extends Text
{
    public function getNamespace(string $filePath): ?string
    {
        $matched  = null;
        $iterator = $this->getIterator();
        $iterator->rewind();
        while ($iterator->valid()) {
            $path      = $iterator->current();
            $namespace = $iterator->key();
            if (str_contains($filePath, $path)) {
                $matched = $namespace;
            }
            $iterator->next();
        }
        return $matched;
    }
}
