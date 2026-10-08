<?php

namespace Subtext\Persistables\Samples;

use DateTimeInterface;
use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Entity;
use Subtext\Persistables\Databases\Attributes\Join;
use Subtext\Persistables\Databases\Attributes\PersistOrder;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Persistable;

#[Table('contacts', 'contactId')]
#[Join('JOIN', 'emails', 'emailId')]
class Contact extends Persistable
{
    #[Column()]
    protected ?int $contactId = null;

    #[Column(unique: true, uqGroup: 'contact', unsigned: true)]
    protected ?int $personId = null;

    #[Entity(order: PersistOrder::BEFORE)]
    protected ?Person $person = null;

    #[Column(unique: true, uqGroup: 'contact', unsigned: true)]
    protected ?int $emailId = null;

    #[Entity(order: PersistOrder::BEFORE)]
    protected ?Email $email = null;

    #[Column(unique: true, uqGroup: 'contact', unsigned: true)]
    protected ?int $phoneNumberId = null;

    #[Entity(order: PersistOrder::BEFORE)]
    protected ?PhoneNumber $phone = null;

    #[Column(unique: true, uqGroup: 'contact', unsigned: true)]
    protected ?int $locationId = null;

    #[Entity(order: PersistOrder::BEFORE)]
    protected ?Location $location = null;

    #[Column()]
    protected DateTimeInterface|string|null $created = null;

    #[Column()]
    protected DateTimeInterface|string|null $updated = null;

    public function jsonSerialize(): mixed
    {
        return (object) [
            'contactId'     => $this->contactId,
            'personId'      => $this->personId,
            'person'        => $this->person,
            'emailId'       => $this->emailId,
            'email'         => $this->email,
            'phoneNumberId' => $this->phoneNumberId,
            'phone'         => $this->phone,
            'locationId'    => $this->locationId,
            'location'      => $this->location,
        ];
    }

    public function getContactId(): ?int
    {
        return $this->contactId;
    }

    public function setContactId(?int $contactId): void
    {
        $this->modify('contactId', $contactId);
    }

    public function getPersonId(): ?int
    {
        return $this->personId;
    }

    public function setPersonId(?int $personId): void
    {
        $this->modify('personId', $personId);
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(Person $person): void
    {
        if ($person->getPersonId() !== null) {
            $this->setPersonId($person->getPersonId());
        }
        $this->person = $person;
    }

    public function getEmailId(): ?int
    {
        return $this->emailId;
    }

    public function setEmailId(?int $emailId): void
    {
        $this->modify('emailId', $emailId);
    }

    public function getEmail(): ?Email
    {
        return $this->email;
    }

    public function setEmail(Email $email): void
    {
        if ($email->getEmailId() !== null) {
            $this->setEmailId($email->getEmailId());
        }
        $this->email = $email;
    }

    public function getPhoneNumberId(): ?int
    {
        return $this->phoneNumberId;
    }

    public function setPhoneNumberId(?int $phoneNumberId): void
    {
        $this->modify('phoneNumberId', $phoneNumberId);
    }

    public function getPhone(): ?PhoneNumber
    {
        return $this->phone;
    }

    public function setPhone(PhoneNumber $phone): void
    {
        if ($phone->getPhoneNumberId() !== null) {
            $this->setPhoneNumberId($phone->getPhoneNumberId());
        }
        $this->phone = $phone;
    }

    public function getLocationId(): ?int
    {
        return $this->locationId;
    }

    public function setLocationId(?int $locationId): void
    {
        $this->modify('locationId', $locationId);
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(Location $location): void
    {
        if ($location->getLocationId() !== null) {
            $this->setLocationId($location->getLocationId());
        }
        $this->location = $location;
    }
}
