<?php

namespace Subtext\Persistables\Databases\Attributes;

use Attribute;

/**
 * Attribute to specify database table metadata for a persistable class.
 *
 * Use this attribute to define the root source of your entity in the database.
 * The entity's data may be spread across multiple tables, though this attribute
 * should define the main table from which all joins are dependent.
 *
 * Example:
 * ```php
 * #[Table(name: 'a')
 * class Entity
 * {
 *     #[Column(name: 'alpha_id', primary: true)
 *     protected ?int $alphaId = null;
 *
 *     #[Column(name: 'beta_id')
 *     protected ?int $betaId = null;
 *
 *     #[Column(name: 'gamma_id')
 *     protected ?int $gammaId = null;
 * }
 * ```
 * This produces an SQL select statement equivalent to:
 * SELECT `a`.`alpha_id` AS `alphaId`,
 *        `a`.`beta_id` AS `betaId`,
 *        `a`.`gamma_id` AS `gammaId`
 * FROM `a`
 * WHERE `a`.`alpha_id` = ?
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Table
{
    /**
     * Constructor for the Table attribute.
     *
     * @param string      $name       The name of the database table associated with the class.
     * @param string|null $primaryKey Optional primary key column name for the table.
     *                               If not provided, the primary key may be inferred
     *                               from columns marked with {@see Column::$primary}.
     */
    public function __construct(
        public string $name,
        public ?string $primaryKey = null
    ) {
    }
}
