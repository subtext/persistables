<?php

namespace Subtext\Persistables;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use JsonException;
use DateMalformedStringException;

trait Hydrator
{
    /**
     * @param mixed $value  The value to be interpreted
     * @param bool $null    Whether to return a null value
     *
     * @return int|null
     */
    protected function getIntegerValue(mixed $value, bool $null = true): ?int
    {
        if (is_numeric($value)) {
            $integer = (int)$value;
        } elseif (is_countable($value)) {
            $integer = count($value);
        } elseif ($null) {
            $integer = null;
        } else {
            $integer = 0;
        }
        return $integer;
    }

    /**
     * @param mixed $value      The value currently bound to the parameter
     * @param bool $null        Whether to return a null value
     * @param string $default   The default value to be returned
     * @param bool $immutable   Whether the return type should be immutable
     *
     * @return DateTimeInterface|null
     * @throws DateMalformedStringException
     */
    protected function getDateValue(
        mixed $value,
        bool $null = true,
        string $default = '0000-00-00 00:00:00',
        bool $immutable = false
    ): ?DateTimeInterface {
        if ($value instanceof DateTimeInterface) {
            $date = $value;
        } elseif (is_string($value) && !empty($value)) {
            if ($immutable) {
                $date = new DateTimeImmutable($value);
            } else {
                $date = new DateTime($value);
            }
        } elseif ($null) {
            $date = null;
        } elseif ($immutable) {
            $date = new DateTimeImmutable($default);
        } else {
            $date = new DateTime($default);
        }
        return $date;
    }

    /**
     * Gets the formatted date from a DateTime object.
     *
     * If the default datetime 0000-00-00 00:00:00 was used to create $date,
     * returns null. Otherwise, returns the $date string formatted using DateTime::ATOM.
     *
     * @param DateTimeInterface|null $date
     *
     * @return string|null
     */
    public function getFormattedDate(?DateTimeInterface $date): ?string
    {
        if ($date instanceof DateTimeInterface && $date->getTimestamp() > -62169962964) {
            $date = $date->format(DateTime::ATOM);
        } else {
            $date = null;
        }
        return $date;
    }

    /**
     * Return a boolean value.
     *
     * @param $value
     * @return bool
     */
    protected function getBooleanValue($value): bool
    {
        $bool = false;
        if (is_bool($value)) {
            $bool = $value;
        } elseif (is_numeric($value)) {
            $bool = (bool) $value;
        } elseif (is_string($value)) {
            $clean = trim(strtolower($value));
            if (!in_array($clean, ['false', 'no', 'off', 'n', ''])) {
                if (in_array($clean, ['true', 'yes', 'on', 'y'])) {
                    $bool = true;
                }
            }
        }
        return $bool;
    }

    /**
     * Parse a json string value into practically anything. Returns null when a
     * json parsing error occurs.
     *
     * @param string $value
     * @return mixed
     */
    protected function getJsonValue(string $value): mixed
    {
        try {
            $value = json_decode($value, flags: JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_IGNORE);
        } catch (JsonException $e) {
            $value = null;
        }
        return $value;
    }
}
