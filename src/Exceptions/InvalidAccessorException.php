<?php

namespace Subtext\Persistables\Exceptions;

use Subtext\Persistables\Exceptions\SchemaDefinitionException;

class InvalidAccessorException extends SchemaDefinitionException
{
    public static function create(string $class, string $method): self
    {
        return new InvalidAccessorException(sprintf(
            'Method %s not found in class %s',
            $method,
            $class
        ));
    }
}
