<?php

namespace Subtext\Persistables\Samples;

use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Modifications;
use Subtext\Persistables\Persistable;

#[Table(name: 'people', primaryKey: 'personId')]
class Person extends Persistable
{
    #[Column()]
    protected ?int $personId = null;

    #[Column(limit: 12, nullable: false, unique: true, uqGroup: 'person')]
    protected string $prefix = '';

    #[Column(limit: 128, nullable: false, unique: true, uqGroup: 'person')]
    protected string $first = '';

    #[Column(limit: 128, nullable: false, unique: true, uqGroup: 'person')]
    protected string $middle = '';

    #[Column(limit: 128, nullable: false, unique: true, uqGroup: 'person')]
    protected string $last = '';

    #[Column(limit: 128, nullable: false, unique: true, uqGroup: 'person')]
    protected string $suffix = '';

    public function __construct()
    {
        $this->modified = new Modifications\Collection();
    }

    public function getPersonId(): ?int
    {
        return $this->personId;
    }

    public function setPersonId(?int $personId): void
    {
        $this->modify('personId', $personId);
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function setPrefix(?string $prefix): void
    {
        $this->modify('prefix', $prefix);
    }

    public function getFirst(): ?string
    {
        return $this->first;
    }

    public function setFirst(?string $first): void
    {
        $this->modify('first', $first);
    }

    public function getMiddle(): ?string
    {
        return $this->middle;
    }

    public function setMiddle(?string $middle): void
    {
        $this->modify('middle', $middle);
    }

    public function getLast(): ?string
    {
        return $this->last;
    }

    public function setLast(?string $last): void
    {
        $this->modify('last', $last);
    }

    public function getSuffix(): ?string
    {
        return $this->suffix;
    }

    public function setSuffix(?string $suffix): void
    {
        $this->modify('suffix', $suffix);
    }

    public function jsonSerialize(): mixed
    {
        return (object) [
            'personId' => $this->personId,
            'prefix'   => $this->prefix,
            'first'    => $this->first,
            'middle'   => $this->middle,
            'last'     => $this->last,
            'suffix'   => $this->suffix,
        ];
    }
}
