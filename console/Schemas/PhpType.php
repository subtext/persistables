<?php

namespace Subtext\Persistables\Console\Schemas;

enum PhpType: string
{
    case STRING            = 'string';
    case INTEGER           = 'int';
    case FLOAT             = 'float';
    case BOOL              = 'bool';
    case ARRAY             = 'array';
    case OBJECT            = 'object';
    case DATETIME          = 'DateTime';
    case DATETIMEINTERFACE = 'DateTimeInterface';
    case DATETIMEIMMUTABLE = 'DateTimeImmutable';
    case JSONSERIALIZABLE  = 'JsonSerializable';
}
