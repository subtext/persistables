<?php

namespace Subtext\Persistables\Queries;

/**
 * A container for storing an SQL query and its associated data. The SqlCommand
 * is used with transactions in the database. A collection of commands can be
 * applied, if any fail, the whole thing is rolled back.
 */
class SqlCommand
{
    private string $query;
    private array $data;

    public function __construct(string $query, array $data)
    {
        $this->query = $query;
        $this->data  = $data;
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
