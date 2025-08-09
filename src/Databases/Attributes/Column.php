<?php

namespace Subtext\Persistables\Databases\Attributes;

use Attribute;

/**
 * Attribute to specify database column metadata for a persistable class.
 *
 * Use this attribute to define metadata for SQL table columns mapped to
 * properties on the targeted class.
 *
 * Example:
 *
 * #[Table('a', 'alpha_id')]
 * #[Join('LEFT', 'b', 'beta_id')]
 * #[Join('JOIN', 'c', 'gamma_id', 'id')]
 * class Entity
 * {
 *     #[Column('alpha_id')]
 *     protected ?int $alphaId = null;
 *
 *     #[Column('beta_id')]
 *     protected ?int $betaId = null;
 *
 *     #[Column('name', 'b', readonly: true)]
 *     protected string $betaName;
 *
 *     #[Column('gamma_id')]
 *     protected ?int $gammaId = null;
 *
 *     #[Column('label', 'c', readonly: true)]
 *     protected string $gammaLabel;
 * }
 *
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    /**
     * Constructor for the Column attribute.
     *
     * @param string|null $name     The name of the database column.
     *                              If null, the property name is used as the column name.
     * @param string|null $table    Optional name of the database table, {@see Table} attribute for default.
     *                              Useful when joining tables using the {@see Join} attribute.
     * @param bool        $primary  Whether this column is a primary key.
     *                              If true, and $table is provided, this column is
     *                              treated as the primary key for that table.
     * @param bool        $readonly If true, this property is read-only and will not be
     *                              saved back to the database. Typically used for
     *                              projection or computed values.
     */
    public function __construct(
        public ?string $name = null,
        public ?string $table = null,
        public bool $primary = false,
        public bool $readonly = false
    ) {
    }
}
