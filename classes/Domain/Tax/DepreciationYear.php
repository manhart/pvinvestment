<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain\Tax;

final class DepreciationYear
{
    public function __construct(
        public readonly int $year,
        public readonly string $assetName,
        public readonly float $openingBookValue,
        public readonly float $regularDepreciation,
        public readonly float $specialDepreciation,
        public readonly float $closingBookValue,
        public readonly int $depreciationMonths,
        public readonly string $methodUsed,
    ) {}

    public function totalDepreciation(): float
    {
        return $this->regularDepreciation + $this->specialDepreciation;
    }
}
