<?php

namespace Subtext\Persistables\Databases\Connections;

class Auth
{
    public function __construct(
        public string $database,
        public string $hostname,
        public string $username,
        public string $password,
        public string $charset,
    ) {
    }
}
