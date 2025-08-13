<?php

namespace Subtext\Persistables\Databases\Attributes;

use Attribute;
use Subtext\Persistables\Collection;

/**
 * Attribute to specify an owned relationship to another persistable class.
 *
 * Use this attribute to define a property which is a Persistable or Collection
 * which will be automatically loaded when the owner is retrieved from the
 * database.
 *
 * Example:
 * ```php
 * #[Table('beta', 'betaId')]
 * class Beta extends Persistable
 * {
 *
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Entity
{
    /**
     * @param string|null $class   Class name of the referenced persistable.
     *                             If null, inferred from the property's type.
     * @param string|null $foreign Column on this {@see Table} that corresponds
     *                             to the primary key of the referenced class.
     *                             If null, inferred from {@see Table::primaryKey}
     *                             or {@see Column::$primary}.
     * @param bool $nullable       If true, the value returned from the accessor
     *                             may be null.
     * @param bool $collection     If true, the value returned from the accessor
     *                             is a {@see Collection}.
     * @param string|null $getter  The method name of the getter. If null, infers
     *                             the name from the property.
     * @param string|null $setter  The method name of the setter. If null, infers
     *                             the name from the property.
     */
    public function __construct(
        public ?string $class = null,
        public ?string $foreign = null,
        public bool $nullable = false,
        public bool $collection = false,
        public ?string $getter = null,
        public ?string $setter = null
    ) {
    }
}
