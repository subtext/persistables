<?php

namespace Subtext\Persistables\Samples;

use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Persistable;

#[Table('postalCodes', 'postalCodeId')]
class PostalCode extends Persistable
{
    #[Column()]
    protected ?int $postalCodeId = null;

    #[Column(nullable: false, unique: true, uqGroup: 'postalCountryCode', index: true)]
    protected ?int $countryId = null;

    #[Column(limit: 9, nullable: false, unique: true, uqGroup: 'postalCountryCode', index: true)]
    protected ?string $code = null;

    public function __construct()
    {
    }

    public function getPostalCodeId(): ?int
    {
        return $this->postalCodeId;
    }

    public function setPostalCodeId(?int $postalCodeId): void
    {
        $this->modify('postalCodeId', $postalCodeId);
    }

    public function getCountryId(): ?int
    {
        return $this->countryId;
    }

    public function setCountryId(int $countryId): void
    {
        $this->modify('countryId', $countryId);
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
            'postalCodeId' => $this->getPostalCodeId(),
            'countryId'    => $this->getCountryId(),
            'code'         => $this->getCode(),
        ];
    }
}
