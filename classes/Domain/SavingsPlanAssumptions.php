<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain;

use InvalidArgumentException;

final class SavingsPlanAssumptions
{
    public function __construct(
        public readonly float $startingCapital = 0.0,
        public readonly float $monthlyContribution = 0.0,
        public readonly float $annualContribution = 0.0,
        public readonly float $positiveCashflowReinvestmentRate = 0.0,
    ) {
        if($startingCapital < 0.0) {
            throw new InvalidArgumentException('Starting capital must not be negative.');
        }
        if($monthlyContribution < 0.0) {
            throw new InvalidArgumentException('Monthly contribution must not be negative.');
        }
        if($annualContribution < 0.0) {
            throw new InvalidArgumentException('Annual contribution must not be negative.');
        }
        if($positiveCashflowReinvestmentRate < 0.0 || $positiveCashflowReinvestmentRate > 1.0) {
            throw new InvalidArgumentException('Positive cashflow reinvestment rate must be between 0 and 1.');
        }
    }

    public function annualContributionFromCashflow(float $annualInvestorCashflow): float
    {
        return max(0.0, $annualInvestorCashflow) * $this->positiveCashflowReinvestmentRate;
    }

    public function fixedAnnualContribution(float $monthlyContributionMonthFactor = 1.0, bool $includeAnnualContribution = true): float
    {
        return ($includeAnnualContribution ? $this->annualContribution : 0.0)
            + ($this->monthlyContribution * 12.0 * $monthlyContributionMonthFactor);
    }
}
