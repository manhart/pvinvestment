<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain\Time;

use InvalidArgumentException;

final class YearMonth
{
    public function __construct(
        public readonly int $year,
        public readonly int $month,
    ) {
        if($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Month must be between 1 and 12.');
        }
    }

    public function isOnOrAfter(self $other): bool
    {
        return $this->year > $other->year
            || ($this->year === $other->year && $this->month >= $other->month);
    }

    public function next(): self
    {
        if($this->month === 12) {
            return new self($this->year + 1, 1);
        }

        return new self($this->year, $this->month + 1);
    }

    public function equals(self $other): bool
    {
        return $this->year === $other->year && $this->month === $other->month;
    }
}
