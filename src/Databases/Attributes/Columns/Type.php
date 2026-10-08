<?php

namespace Subtext\Persistables\Databases\Attributes\Columns;

/**
 * Represents a subset of the basic storage types available in an SQL database.
 * These types will be mapped to a final representation for a specific SQL flavor.
 */
enum Type: string
{
    case INTEGER   = 'integer';
    case LONG      = 'long';
    case DECIMAL   = 'decimal';
    case FLOAT     = 'float';
    case CHARACTER = 'char';
    case STRING    = 'varchar';
    case TEXT      = 'text';
    case DATE      = 'date';
    case DATETIME  = 'datetime';
    case BOOL      = 'bool';
    case JSON      = 'json';
}
