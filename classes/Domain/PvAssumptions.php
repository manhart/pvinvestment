<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain;

use InvalidArgumentException;

final class PvAssumptions
{
    public function __construct(
        public readonly float $annualRevenue,
        public readonly float $annualOperatingCosts = 0.0,
    ) {
        if($annualRevenue < 0.0) {
            throw new InvalidArgumentException('Annual PV revenue must not be negative.');
        }
        if($annualOperatingCosts < 0.0) {
            throw new InvalidArgumentException('Annual PV operating costs must not be negative.');
        }
    }

    public function annualNetCashflow(): float
    {
        return $this->annualRevenue - $this->annualOperatingCosts;
    }

    public function revenueForMonthFactor(float $monthFactor): float
    {
        return $this->annualRevenue * $monthFactor;
    }

    public function operatingCostsForMonthFactor(float $monthFactor): float
    {
        return $this->annualOperatingCosts * $monthFactor;
    }
}
