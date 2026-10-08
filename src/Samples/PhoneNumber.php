<?php

namespace Subtext\Persistables\Samples;

use InvalidArgumentException;
use Subtext\Persistables\Databases\Attributes\Column;
use Subtext\Persistables\Databases\Attributes\Join;
use Subtext\Persistables\Databases\Attributes\Table;
use Subtext\Persistables\Modifications;
use Subtext\Persistables\Persistable;

#[Table(name: 'phoneNumbers', primaryKey: 'phoneNumberId')]
#[Join(type: 'LEFT', table: 'countries', key: 'countryId')]
class PhoneNumber extends Persistable
{
    public const string REGEX_PHONE = '/^(?:\+?(1|52)[\s.\-]?)?(?:\(?(\d{2,3})\)?[\s.\-]?)(\d{3,4})[\s.\-]?(\d{4})$/';
    public const string REGEX_LABEL = '/^[\w ]{1,64}$/';

    #[Column()]
    protected ?int $phoneNumberId = null;

    #[Column(nullable: false, index: true)]
    protected ?int $countryId = null;

    #[Column(name: 'name', table: 'countries', readonly: true, index: true)]
    protected ?string $countryName = null;

    #[Column(limit: 10, unique: true, uqGroup: 'phoneNumber')]
    protected string $number = '';

    #[Column(limit: 64, unique: true, uqGroup: 'phoneNumber')]
    protected string $extension = '';

    #[Column(limit: 64, unique: true, uqGroup: 'phoneNumber')]
    protected string $label = '';

    public function __construct()
    {
        $this->modified = new Modifications\Collection();
    }

    public function getPhoneNumberId(): ?int
    {
        return $this->phoneNumberId;
    }

    public function setPhoneNumberId(?int $phoneNumberId): void
    {
        $this->modify('phoneNumberId', $phoneNumberId);
    }

    public function getCountryId(): ?int
    {
        return $this->countryId;
    }

    public function setCountryId(int $countryId): void
    {
        $this->modify('countryId', $countryId);
    }

    public function getCountryName(): ?string
    {
        return $this->countryName;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getNumberFormatted(): string
    {
        $number = '';
        if (strlen($this->number) === 10) {
            $format = '(%s) %s-%s';
            if ($this->countryId === 3) {
                // Mexico
                $number = '+52 ' . sprintf(
                    $format,
                    substr($this->number, 0, 2),
                    substr($this->number, 2, 4),
                    substr($this->number, 6)
                );
            } else {
                // US & Canada
                $number = '+1 ' . sprintf(
                    $format,
                    substr($this->number, 0, 3),
                    substr($this->number, 3, 3),
                    substr($this->number, 6)
                );
            }
        }
        return $number;
    }

    public function setNumber(string $number): void
    {
        if (!preg_match(self::REGEX_PHONE, $number, $matches)) {
            throw new InvalidArgumentException(
                'The phone number provided is not valid.'
            );
        }
        $this->modify('number', $matches[2] . $matches[3] . $matches[4]);
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): void
    {
        if (!preg_match(self::REGEX_LABEL, $extension, $matches)) {
            throw new InvalidArgumentException(
                'The phone extension must be 64 or fewer alphanumeric characters.'
            );
        }
        $this->modify('extension', $extension);
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): void
    {
        if (!preg_match(self::REGEX_LABEL, $label)) {
            throw new InvalidArgumentException(
                'The phone label must 64 or fewer alphanumeric characters.'
            );
        }
        $this->modify('label', trim(strtolower($label)));
    }

    public function jsonSerialize(): mixed
    {
        return (object) [
            'phoneNumberId' => $this->getPhoneNumberId(),
            'countryId'     => $this->getCountryId(),
            'number'        => $this->getNumber(),
            'extension'     => $this->getExtension(),
            'label'         => $this->getLabel(),
        ];
    }
}
