<?php

namespace Subtext\Persistables\Databases\Attributes;

use Attribute;
use InvalidArgumentException;

/**
 * Attribute to specify a database join metadata for a persistable class.
 *
 * Use this attribute to define a JOIN clause metadata for the class,
 * specifying the type of join, the target table, and key relationships.
 *
 * This attribute can be applied multiple times on the same class.
 *
 * Example:
 * ```php
 * #[Table(name: 'a', primaryKey: 'alpha_id')]
 * #[JOIN(type: 'LEFT', table: 'b', key: 'beta_id')
 * #[JOIN(type: 'JOIN', table: 'c', key: 'gamma_id', foreign: 'id')
 * class Entity {...}
 * ```
 *  This produces a join condition equivalent to:
 *  FROM `a`
 *  LEFT JOIN `b` ON `a`.`beta_id` = `b`.`beta_id`
 *  JOIN `c` ON `a`.`gamma_id` = `c`.`id`
 *
 *  where 'a' is the base table from the {@see Table} attribute,
 *  and 'b' and 'c' are the joined tables from these join attributes.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Join
{
    public const array TYPES = ['INNER','LEFT','RIGHT','FULL OUTER','JOIN'];

    /**
     * Constructor for the Join attribute.
     *
     * @param string      $type    The type of join (e.g., 'INNER', 'LEFT', 'RIGHT').
     * @param string      $table   The name of the table to be joined, typically against
     *                             {@see Table} attribute's `name` on the joined class.
     * @param string      $key     The column name in the base table (the class annotated
     *                             with {@see Table}) used for joining.
     * @param string|null $foreign Optional foreign key column in the joined table.
     *                             If null, it is assumed the foreign key column
     *                             has the same name as the base key.
     * @param string|null $target  Optional value for targeting join against
     *                             another table besides the base within the join.
     */
    public function __construct(
        public string $type,
        public string $table,
        public string $key,
        public ?string $foreign = null,
        public ?string $target = null
    ) {
        if (!in_array($type, self::TYPES)) {
            throw new InvalidArgumentException(
                'Join type must be one of ' . implode(', ', self::TYPES)
            );
        }
    }
}
