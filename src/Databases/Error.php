<?php

namespace Subtext\Persistables\Databases;

class Error
{
    public function __construct(
        public string $msg,
        public int $code,
        public string $info
    ) {
    }
}
