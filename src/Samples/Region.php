<?php

namespace Subtext\Persistables\Samples;

use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Persistable;

#[Table('regions', 'regionId')]
class Region extends Persistable
{
    #[Column()]
    protected ?int $regionId = null;

    #[Column(nullable: false, unique: true, uqGroup: 'regionName', index: true)]
    protected ?int $countryId = null;

    #[Column(limit: 64, nullable: false, unique: true, uqGroup: 'regionName')]
    protected ?string $name = null;

    #[Column(limit: 3, nullable: false, unique: true, uqGroup: 'regionName', index: true)]
    protected ?string $code = null;

    public function __construct()
    {

    }

    public function getRegionId(): ?int
    {
        return $this->regionId;
    }

    public function setRegionId(?int $regionId): void
    {
        $this->modify('regionId', $regionId);
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
            'regionId'  => $this->getRegionId(),
            'countryId' => $this->getCountryId(),
            'name'      => $this->getName(),
            'code'      => $this->getCode(),
        ];
    }
}
