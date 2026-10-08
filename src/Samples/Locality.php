<?php

namespace Subtext\Persistables\Samples;

use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Entity;
use Subtext\Persistables\Databases\Attributes\PersistOrder;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Persistable;

#[Table('localities', 'localityId')]
class Locality extends Persistable
{
    #[Column()]
    protected ?int $localityId = null;

    #[Column(nullable: false, unique:true, uqGroup: 'localityName', index: true)]
    protected ?int $regionId = null;

    #[Entity(order: PersistOrder::BEFORE)]
    protected ?Region $region = null;

    #[Column(limit: 128, nullable: false, unique: true, uqGroup: 'localityName')]
    protected ?string $name = null;

    public function __construct()
    {
    }

    public function getLocalityId(): ?int
    {
        return $this->localityId;
    }

    public function setLocalityId(?int $localityId): void
    {
        $this->modify('localityId', $localityId);
    }

    public function getRegionId(): ?int
    {
        return $this->regionId;
    }

    public function setRegionId(int $regionId): void
    {
        $this->modify('regionId', $regionId);
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(Region $region): void
    {
        if ($region->getRegionId() !== null) {
            $this->setRegionId($region->getRegionId());
        }
        $this->region = $region;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->modify('name', $name);
    }

    public function jsonSerialize(): mixed
    {
        return (object) [
            'localityId' => $this->getLocalityId(),
            'regionId'   => $this->getRegionId(),
            'name'       => $this->getName(),
        ];
    }
}
