<?php

namespace Subtext\Persistables\Samples;

use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Persistable;

#[Table(name: 'emails', primaryKey: 'emailId')]
class Email extends Persistable
{
    #[Column()]
    protected ?int $emailId = null;

    #[Column(limit: 255, nullable: false, unique: true, index: true)]
    protected ?string $address = null;

    public function __construct()
    {
    }

    public function getEmailId(): ?int
    {
        return $this->emailId;
    }

    public function setEmailId(?int $emailId): void
    {
        $this->modify("emailId", $emailId);
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->modify("address", $address);
    }

    public function jsonSerialize(): mixed
    {
        return (object) [
            'emailId' => $this->getEmailId(),
            'address' => $this->getAddress(),
        ];
    }
}
