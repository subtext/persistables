<?php

namespace Subtext\Persistables\Samples;

use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Columns\Type;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Modifications;
use Subtext\Persistables\Persistable;

#[Table(name: 'countries', primaryKey: 'countryId')]
class Country extends Persistable
{
    #[Column()]
    protected ?int $countryId = null;

    #[Column(limit: 196, nullable: false, unique: true, uqGroup: 'countryName')]
    protected ?string $name = null;

    #[Column(type: Type::CHAR, limit: 2, nullable: false, unique: true, uqGroup: 'countryName')]
    protected ?string $code = null;

    public function __construct()
    {
        $this->modified = new Modifications\Collection();
    }

    public function getCountryId(): ?int
    {
        return $this->countryId;
    }

    public function setCountryId(int $countryId): void
    {
        $this->modify('countryId', $countryId);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->modify('name', $name);
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->modify('code', $code);
    }

    public function jsonSerialize(): mixed
    {
        return (object) [
            'countryId' => $this->getCountryId(),
            'name'      => $this->getName(),
            'code'      => $this->getCode(),
        ];
    }
}
