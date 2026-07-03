<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain;

final class TaxCalculationResult
{
    public function __construct(
        public readonly float $taxableRevenue,
        public readonly float $deductibleOperatingCosts,
        public readonly float $deductibleInterest,
        public readonly float $immediatelyDeductibleCosts,
        public readonly float $capitalizableAcquisitionCosts,
        public readonly float $iabDeduction,
        public readonly float $iabAddition,
        public readonly float $iabAcquisitionCostReduction,
        public readonly float $depreciationBasis,
        public readonly float $regularDepreciation,
        public readonly float $specialDepreciation,
        public readonly float $taxableIncome,
        public readonly float $taxAmount,
        public readonly int $taxPaymentYear,
        public readonly float $cashflowTaxPayment,
        public readonly float $taxableResultBeforeLoss = 0.0,
        public readonly float $lossUsed = 0.0,
        public readonly float $lossCreated = 0.0,
        public readonly float $lossCarriedForward = 0.0,
        public readonly float $taxableResultAfterLoss = 0.0,
        public readonly int $taxCashflowYear = 0,
        public readonly int $taxCashflowMonth = 12,
        public readonly float $iabEligibleInvestmentBasis = 0.0,
    ) {}

    public function totalDepreciation(): float
    {
        return $this->regularDepreciation + $this->specialDepreciation;
    }
}
