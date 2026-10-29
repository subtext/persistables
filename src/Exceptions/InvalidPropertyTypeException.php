<?php

namespace Subtext\Persistables\Exceptions;

use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;

class InvalidPropertyTypeException extends SchemaDefinitionException
{
    public static function create(ReflectionProperty $property): self
    {
        $propertyClass = $property->getDeclaringClass()->getName();
        $propertyName  = $property->getName();
        $type          = $property->getType();
        if ($type === null) {
            $message = sprintf(
                "The property: %s of class %s has no defined type.",
                $propertyName,
                $propertyClass
            );
        } elseif ($type instanceof ReflectionNamedType) {
            $message = sprintf(
                "The property: %s of class %S has an invalid type: %s",
                $propertyName,
                $propertyClass,
                $type->getName()
            );
        } elseif ($type instanceof ReflectionIntersectionType) {
            $message = sprintf(
                "Intersection types are not supported on the property: %s of class %s.",
                $propertyName,
                $propertyClass
            );
        } else {
            $message = "";
        }
        return new self($message);
    }
}
