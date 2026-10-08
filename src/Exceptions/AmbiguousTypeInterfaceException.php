<?php

namespace Subtext\Persistables\Exceptions;

use ReflectionProperty;

class AmbiguousTypeInterfaceException extends SchemaDefinitionException
{
    public static function create(ReflectionProperty $property): self
    {
        return new self(sprintf(
            'The type definition for property: %s on class: %s is ambiguous',
            $property->getName(),
            $property->getDeclaringClass()->getName()
        ));
    }
}
