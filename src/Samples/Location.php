<?php

namespace Subtext\Persistables\Samples;

use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Entity;
use Subtext\Persistables\Databases\Attributes\PersistOrder;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Persistable;

#[Table('locations', 'locationId')]
class Location extends Persistable
{
    #[Column()]
    protected ?int $locationId = null;

    #[Column(limit: 128, nullable: false, unique: true, uqGroup: 'streetAddress')]
    protected ?string $street = null;

    #[Column(limit: 128, nullable: false, unique: true, uqGroup: 'streetAddress')]
    protected ?string $unit = null;

    #[Column(nullable: false, unique: true, uqGroup: 'streetAddress')]
    protected ?int $localityId = null;

    #[Entity(order: PersistOrder::BEFORE)]
    protected ?Locality $locality = null;

    #[Column(nullable: false, unique: true, uqGroup: 'streetAddress')]
    protected ?int $postalCodeId = null;

    #[Entity(order: PersistOrder::BEFORE)]
    protected ?PostalCode $postalCode = null;

    public function __construct()
    {
    }

    public function getLocationId(): ?int
    {
        return $this->locationId;
    }

    public function setLocationId(?int $locationId): void
    {
        $this->modify('locationId', $locationId);
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): void
    {
        $this->modify('street', $street);
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): void
    {
        $this->modify('unit', $unit);
    }

    public function getLocalityId(): ?int
    {
        return $this->localityId;
    }

    public function setLocalityId(int $localityId): void
    {
        $this->modify('localityId', $localityId);
    }

    public function getLocality(): ?Locality
    {
        return $this->locality;
    }

    public function setLocality(Locality $locality): void
    {
        if ($locality->getLocalityId() !== null) {
            $this->setLocalityId($locality->getLocalityId());
        }
        $this->locality = $locality;
    }

    public function getPostalCodeId(): ?int
    {
        return $this->postalCodeId;
    }

    public function setPostalCodeId(int $postalCodeId): void
    {
        $this->modify('postalCodeId', $postalCodeId);
    }

    public function getPostalCode(): ?PostalCode
    {
        return $this->postalCode;
    }

    public function setPostalCode(PostalCode $postalCode): void
    {
        if ($postalCode->getPostalCodeId() !== null) {
            $this->setPostalCodeId($postalCode->getPostalCodeId());
        }
        $this->postalCode = $postalCode;
    }

    public function jsonSerialize(): mixed
    {
        return (object) [
            'locationId'   => $this->getLocationId(),
            'street'       => $this->getStreet(),
            'unit'         => $this->getUnit(),
            'localityId'   => $this->getLocalityId(),
            'postalCodeId' => $this->getPostalCodeId(),
        ];
    }
}
