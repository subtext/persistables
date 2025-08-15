<?php

namespace Subtext\Persistables\Databases;

use Subtext\Persistables\Modifications\Collection;

interface SqlGenerator
{
    /**
     * Generate a basic select using the primary key of an entity.
     *
     * @param Meta $meta
     * @param string|null $clause
     * @return string
     */
    public function getSelectQuery(Meta $meta, ?string $clause = null): string;

    /**
     * Generate a basic SQL insert statement based on the data provided.
     *
     * @param Meta $meta
     * @param int $rows
     * @return string
     */
    public function getInsertQuery(Meta $meta, int $rows = 1): string;

    /**
     * Generate a basic SQL update statement, using only the columns which have
     * been modified.
     *
     * @param Meta $meta
     * @param Collection $modifications
     * @return string
     */
    public function getUpdateQuery(Meta $meta, Collection $modifications): string;

    /**
     * Generate a basic SQL delete statement using the primary key for the entity.
     *
     * @param Meta $meta
     * @param int $count
     * @return string
     */
    public function getDeleteQuery(Meta $meta, int $count = 1): string;
}
