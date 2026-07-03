<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain;

use InvalidArgumentException;

final class ProjectTimingAssumptions
{
    public function __construct(
        public readonly int $calculationYear = 2026,
        public readonly int $investmentYear = 2026,
        public readonly int $investmentMonth = 1,
        public readonly int $depreciationStartYear = 2026,
        public readonly int $depreciationStartMonth = 1,
        public readonly int $eegCommissioningYear = 2026,
        public readonly int $eegCommissioningMonth = 1,
        public readonly int $gridConnectionYear = 2026,
        public readonly int $gridConnectionMonth = 1,
        public readonly int $revenueStartYear = 2026,
        public readonly int $revenueStartMonth = 1,
        public readonly int $interestStartYear = 2026,
        public readonly int $interestStartMonth = 1,
        public readonly int $repaymentStartYear = 2026,
        public readonly int $repaymentStartMonth = 1,
        public readonly int $taxPaymentYear = 2026,
        public readonly int $savingsPlanContributionStartYear = 2026,
        public readonly int $savingsPlanContributionStartMonth = 1,
        public readonly int $annualSavingsPlanContributionMonth = 12,
    ) {
        foreach([
            'investmentMonth' => $investmentMonth,
            'depreciationStartMonth' => $depreciationStartMonth,
            'eegCommissioningMonth' => $eegCommissioningMonth,
            'gridConnectionMonth' => $gridConnectionMonth,
            'revenueStartMonth' => $revenueStartMonth,
            'interestStartMonth' => $interestStartMonth,
            'repaymentStartMonth' => $repaymentStartMonth,
            'savingsPlanContributionStartMonth' => $savingsPlanContributionStartMonth,
            'annualSavingsPlanContributionMonth' => $annualSavingsPlanContributionMonth,
        ] as $label => $month) {
            if($month < 1 || $month > 12) {
                throw new InvalidArgumentException($label.' must be between 1 and 12.');
            }
        }
    }

    public static function fromTaxAssumptions(TaxAssumptions $taxAssumptions): self
    {
        return new self(
            calculationYear: $taxAssumptions->calculationYear,
            depreciationStartYear: $taxAssumptions->depreciationStartYear,
            depreciationStartMonth: $taxAssumptions->depreciationStartMonth,
            taxPaymentYear: $taxAssumptions->calculationYear + $taxAssumptions->taxPaymentDelayYears,
        );
    }

    public function revenueMonthFactor(): float
    {
        return $this->monthFactorFrom($this->revenueStartYear, $this->revenueStartMonth);
    }

    public function depreciationMonthFactor(): float
    {
        return $this->monthFactorFrom($this->depreciationStartYear, $this->depreciationStartMonth);
    }

    public function interestMonthFactor(): float
    {
        return $this->monthFactorFrom($this->interestStartYear, $this->interestStartMonth);
    }

    public function repaymentMonthFactor(): float
    {
        return $this->monthFactorFrom($this->repaymentStartYear, $this->repaymentStartMonth);
    }

    public function savingsPlanMonthlyContributionFactor(): float
    {
        return $this->monthFactorFrom($this->savingsPlanContributionStartYear, $this->savingsPlanContributionStartMonth);
    }

    public function includesAnnualSavingsPlanContribution(): bool
    {
        if($this->calculationYear > $this->savingsPlanContributionStartYear) {
            return true;
        }
        if($this->calculationYear < $this->savingsPlanContributionStartYear) {
            return false;
        }
        return $this->annualSavingsPlanContributionMonth >= $this->savingsPlanContributionStartMonth;
    }

    private function monthFactorFrom(int $startYear, int $startMonth): float
    {
        if($this->calculationYear < $startYear) {
            return 0.0;
        }
        if($this->calculationYear === $startYear) {
            return (13 - $startMonth) / 12.0;
        }
        return 1.0;
    }
}

