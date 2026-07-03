<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain;

use InvalidArgumentException;

final class PercentageRate
{
    private function __construct(
        private readonly float $decimal,
    ) {
        if($decimal < 0.0) {
            throw new InvalidArgumentException('Percentage rate must not be negative.');
        }
    }

    public static function fromDecimal(float $decimal): self
    {
        return new self($decimal);
    }

    public static function fromPercent(float $percent): self
    {
        return new self($percent / 100.0);
    }

    public static function fromBasisPoints(int $basisPoints): self
    {
        return new self($basisPoints / 10000.0);
    }

    public function toDecimal(): float
    {
        return $this->decimal;
    }

    public function toPercent(): float
    {
        return $this->decimal * 100.0;
    }

    public function toBasisPoints(): int
    {
        return (int)round($this->decimal * 10000.0, 0, PHP_ROUND_HALF_UP);
    }
}

