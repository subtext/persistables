<?php

namespace Subtext\Persistables\Exceptions;

use Exception;

class SchemaDefinitionException extends Exception
{
    public function __construct(string $message, int $code = 500, ?Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
