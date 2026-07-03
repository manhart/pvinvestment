<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain;

final class MoneyAmount
{
    private function __construct(
        private readonly int $cents,
    ) {}

    public static function fromEuro(float $euro): self
    {
        return new self((int)round($euro * 100.0, 0, PHP_ROUND_HALF_UP));
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function multiplyRate(PercentageRate $rate): self
    {
        return self::fromEuro($this->toEuro() * $rate->toDecimal());
    }

    public function toCents(): int
    {
        return $this->cents;
    }

    public function toEuro(): float
    {
        return $this->cents / 100.0;
    }
}

