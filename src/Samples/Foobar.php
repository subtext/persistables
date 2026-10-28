<?php

namespace Subtext\Persistables\Samples;

use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Join;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Persistable;

#[Table('foobar', 'id')]
#[Join('LEFT', 'other', 'key1')]
#[Join('LEFT', 'alternate', 'key2', 'key_id')]
class Foobar extends Persistable
{
    #[Column('id', primary: true)]
    protected ?int $id = null;
    protected string $alpha;
    protected string $beta;

    #[Column('gamma')]
    protected string $gamma = '';
    protected string $delta;

    #[Column('epsilon')]
    protected string $epsilon = '';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->modify('id', $id);
    }

    public function getGamma(): string
    {
        return $this->gamma;
    }

    public function setGamma(string $gamma): void
    {
        $this->modify('gamma', $gamma);
    }

    public function getEpsilon(): string
    {
        return $this->epsilon;
    }

    public function setEpsilon(string $epsilon): void
    {
        $this->modify('epsilon', $epsilon);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return [];
    }
}
