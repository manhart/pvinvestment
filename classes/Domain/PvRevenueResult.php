<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain;

final class PvRevenueResult
{
    public function __construct(
        public readonly int $year,
        public readonly float $annualProductionKwh,
        public readonly float $grossRevenue,
        public readonly float $directMarketingCosts,
        public readonly float $netRevenue,
        public readonly float $degradationFactor,
        public readonly float $priceFactor,
        public readonly bool $manualOverrideUsed = false,
    ) {}
}
