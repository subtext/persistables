<?php

namespace Subtext\Persistables\Tests\Unit;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Hydrator;

class HydratorTest extends TestCase
{
    public function testHydrateIntegerValue(): void
    {
        $unit = new class ("400", ['a', 'b', 'c'], null) {
            use Hydrator;

            public function __construct(
                private mixed $numeric,
                private mixed $countable,
                private mixed $empty
            ) {
            }

            public function getNumeric(): mixed
            {
                return $this->getIntegerValue($this->numeric);
            }

            public function getCountable(): mixed
            {
                return $this->getIntegerValue($this->countable);
            }

            public function getEmpty(): mixed
            {
                return $this->getIntegerValue($this->empty, false);
            }

            public function getNull(): mixed
            {
                return $this->getIntegerValue($this->empty);
            }
        };

        $this->assertIsInt($unit->getNumeric());
        $this->assertIsInt($unit->getCountable());
        $this->assertIsInt($unit->getEmpty());
        $this->assertNull($unit->getNull());
    }

    public function testHydrateDateTimeValue(): void
    {
        $datetime = new DateTime()->format(DateTimeInterface::ATOM);
        $unit     = new class ($datetime, $datetime, new DateTimeImmutable(), null, null) {
            use Hydrator;

            public function __construct(
                private mixed $string,
                private mixed $immutable,
                private mixed $interface,
                private mixed $empty,
                private mixed $default
            ) {
            }

            public function getString(): mixed
            {
                return $this->getDateValue($this->string, false);
            }

            public function getImmutable(): DateTimeImmutable
            {
                return $this->getDateValue($this->immutable, false, immutable: true);
            }

            public function getInterface(): mixed
            {
                return $this->getDateValue($this->interface);
            }

            public function getEmpty(): mixed
            {
                return $this->getDateValue($this->empty);
            }

            public function getDefault(): mixed
            {
                return $this->getDateValue($this->default, false);
            }

            public function getImmutableFromNull(): DateTimeImmutable
            {
                return $this->getDateValue(null, false, immutable: true);
            }
        };

        $this->assertInstanceOf(DateTime::class, $unit->getString());
        $this->assertInstanceOf(DateTimeImmutable::class, $unit->getImmutable());
        $this->assertInstanceOf(DateTimeImmutable::class, $unit->getInterface());
        $this->assertEquals(null, $unit->getEmpty());
        $this->assertEquals(new DateTime('0000-00-00 00:00:00'), $unit->getDefault());
        $this->assertEquals(new DateTimeImmutable('0000-00-00 00:00:00'), $unit->getImmutableFromNull());
    }

    public function testHydrateBooleanValue(): void
    {
        $unit = new class (5, 'false', true) {
            use Hydrator;

            public function __construct(
                private string $numeric,
                private string $string,
                private bool $boolean
            ) {
            }

            public function getNumeric(): bool
            {
                return $this->getBooleanValue($this->numeric);
            }

            public function getString(): bool
            {
                return $this->getBooleanValue($this->string);
            }

            public function setString(string $string): void
            {
                $this->string = $string;
            }

            public function getBoolean(): bool
            {
                return $this->getBooleanValue($this->boolean);
            }
        };

        $this->assertTrue($unit->getNumeric());
        $this->assertFalse($unit->getString());
        $this->assertTrue($unit->getBoolean());

        $unit->setString('Yes');
        $this->assertTrue($unit->getString());
    }

    public function testHydrateJsonValue(): void
    {
        $expected = (object) [
        ];

        $unit = new class (json_encode($expected)) {
            use Hydrator;
            protected string $json;

            public function __construct(string $json)
            {
                $this->json = $json;
            }

            public function getJson(): mixed
            {
                return $this->getJsonValue($this->json);
            }

            public function getNullOnError(): null
            {
                return $this->getJsonValue('');
            }
        };

        $this->assertEquals($expected, $unit->getJson());
        $this->assertEquals(null, $unit->getNullOnError());
    }
}
