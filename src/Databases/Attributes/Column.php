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
     * @param string|null $name       The name of the database column. If null, the
     *                                property name is used as the column name.
     * @param string|null $table      Optional name of the database table, {@see Table}
     *                                attribute for default. Useful when joining
     *                                tables using the {@see Join} attribute.
     * @param bool $primary           Whether this column is a primary key. If
     *                                true, and $table is provided, this column is
     *                                treated as the primary key for that table.
     * @param bool $readonly          If true, this property is read-only and will not
     *                                be saved back to the database. Typically used for
     *                                projection or computed values.
     * @param string|null $getter     The method name of the getter. If null, the
     *                                method name is inferred from the property.
     * @param string|null $setter     The method name of the setter. If null, the
     *                                method name is inferred unless the column is readonly.
     * @param Columns\Type|null $type The SQL column type, inferred from the property
     *                                type if this property is null.
     * @param int $limit              The character limit for string types. Defaults
     *                                to 255.
     * @param bool|null $nullable     Whether this column may be null. If null, the
     *                                value is inferred from the property type.
     * @param bool|null $increment    Whether the column value should auto-increment.
     *                                If null, metadata inference resolves this to false.
     * @param int|null $precision     The total number of digits for numeric types.
     *                                Commonly used with DECIMAL and NUMERIC columns.
     * @param int|null $scale         The number of digits after the decimal point for
     *                                numeric types.
     * @param bool|null $unique       Whether this column must have unique values.
     *                                If true, a unique index is implied.
     * @param string|null $uqGroup    Evaluated only when unique is set to true.
     *                                If set, the unique constraint includes this
     *                                column as part of the named group.
     * @param bool|null $index        Whether this column should be indexed.
     *                                Implies a non-unique index if $unique is false.
     * @param string|null $idxGroup   Evaluated only when index is set to true.
     *                                If set, the column index is part of the
     *                                named group.
     * @param bool|null $unsigned     Whether the column should be unsigned.
     *                                Applies to numeric column types.
     * @param bool $nullDefault       Whether the column explicitly defaults to SQL NULL.
     * @param mixed $default          The default value for this column. Use $nullDefault
     *                                to represent an explicit SQL NULL default.
     */
    public function __construct(
        public ?string $name = null,
        public ?string $table = null,
        public bool $primary = false,
        public bool $readonly = false,
        public ?string $getter = null,
        public ?string $setter = null,
        public ?Columns\Type $type = null,
        public int     $limit = 255,
        public ?bool   $nullable = null,
        public ?bool   $increment = null,
        public ?int    $precision = null,
        public ?int    $scale = null,
        public ?bool   $unique = null,
        public ?string $uqGroup = null,
        public ?bool   $index = null,
        public ?string $idxGroup = null,
        public ?bool   $unsigned = null,
        public bool    $nullDefault = false,
        public mixed   $default = null,
    ) {}
}
