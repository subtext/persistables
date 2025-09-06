<?php

namespace Subtext\Persistables\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Subtext\Persistables\Persistable;

class PersistableTest extends TestCase
{
    public function testModifiedPersistable(): void
    {
        $unit = new class (1, 'a', 'b') extends Persistable {
            protected ?int $id      = null;
            protected string $alpha = '';
            protected string $beta  = '';

            public function __construct(?int $id, string $alpha, string $beta)
            {
                $this->id    = $id;
                $this->alpha = $alpha;
                $this->beta  = $beta;
            }

            public function getId(): ?int
            {
                return $this->id;
            }

            public function setId(?int $id): void
            {
                $this->modify('id', $id);
            }

            public function getAlpha(): string
            {
                return $this->alpha;
            }

            public function setAlpha(string $alpha): void
            {
                $this->modify('alpha', $alpha);
            }

            public function getBeta(): string
            {
                return $this->beta;
            }

            public function setBeta(string $beta): void
            {
                $this->modify('beta', $beta);
            }

            public function jsonSerialize(): mixed
            {
                return (object) [
                    'id'    => $this->getId(),
                    'alpha' => $this->getAlpha(),
                    'beta'  => $this->getBeta(),
                ];
            }
        };

        $this->assertCount(0, $unit->getModified());

        $unit->setAlpha('apple');
        $unit->setBeta('banana');

        $modifications = $unit->getModified();

        $this->assertCount(2, $modifications);
        $this->assertEquals('a', $modifications->getFirst()->getOldValue());
        $this->assertEquals('apple', $modifications->getFirst()->getNewValue());
        $this->assertEquals('b', $modifications->getLast()->getOldValue());
        $this->assertEquals('banana', $modifications->getLast()->getNewValue());

        $this->assertEquals('apple', $unit->getAlpha());
        $this->assertEquals('banana', $unit->getBeta());
        $this->assertEquals(1, $unit->getId());

        $unit->rollbackModifications();
        $this->assertCount(0, $unit->getModified());
        $this->assertEquals('a', $unit->getAlpha());
        $this->assertEquals('b', $unit->getBeta());
    }
}
