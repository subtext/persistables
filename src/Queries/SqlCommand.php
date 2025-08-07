<?php

namespace Subtext\Persistables\Queries;

class SqlCommand
{
    private string $query;
    private array $data;

    public function __construct(string $query, array $data)
    {
        $this->query = $query;
        $this->data = $data;
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
