<?php

namespace Subtext\Persistables\Databases\Attributes;

enum PersistOrder: string
{
    case BEFORE = 'before';
    case AFTER = 'after';
}
