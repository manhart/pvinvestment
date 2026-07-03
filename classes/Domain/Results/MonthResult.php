<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain\Results;

final class MonthResult
{
    public function __construct(
        public readonly int $year,
        public readonly int $month,
        public readonly float $pvRevenue,
        public readonly float $batteryGrossRevenue,
        public readonly float $batteryInvestorRevenue,
        public readonly float $batteryInvestorCosts,
        public readonly float $operatingCosts,
        public readonly float $financingInterest,
        public readonly float $financingPrincipal,
        public readonly float $depreciation,
        public readonly float $taxableResultComponent,
        public readonly float $taxCashflow,
        public readonly float $investorCashflowBeforeSavings,
        public readonly float $savingsContribution,
        public readonly float $savingsReturn,
        public readonly float $savingsTax,
        public readonly float $savingsEndValue,
        public readonly float $freeCashflowAfterSavings,
    ) {}

    /**
     * @return array<string, float|int>
     */
    public function toArray(): array
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'pvRevenue' => $this->pvRevenue,
            'batteryGrossRevenue' => $this->batteryGrossRevenue,
            'batteryInvestorRevenue' => $this->batteryInvestorRevenue,
            'batteryInvestorCosts' => $this->batteryInvestorCosts,
            'operatingCosts' => $this->operatingCosts,
            'financingInterest' => $this->financingInterest,
            'financingPrincipal' => $this->financingPrincipal,
            'depreciation' => $this->depreciation,
            'taxableResultComponent' => $this->taxableResultComponent,
            'taxCashflow' => $this->taxCashflow,
            'investorCashflowBeforeSavings' => $this->investorCashflowBeforeSavings,
            'savingsContribution' => $this->savingsContribution,
            'savingsReturn' => $this->savingsReturn,
            'savingsTax' => $this->savingsTax,
            'savingsEndValue' => $this->savingsEndValue,
            'freeCashflowAfterSavings' => $this->freeCashflowAfterSavings,
        ];
    }
}
