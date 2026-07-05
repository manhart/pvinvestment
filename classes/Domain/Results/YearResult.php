<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain\Results;

final class YearResult
{
    /**
     * @param list<MonthResult> $months
     */
    public function __construct(
        public readonly int $year,
        public readonly array $months,
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
        public readonly float $batteryCapexInvestor = 0.0,
        public readonly float $batteryReplacementCapexInvestor = 0.0,
        public readonly float $batteryDegradationFactor = 1.0,
        public readonly float $batteryRevenueBeforeDegradation = 0.0,
        public readonly float $batteryRevenueAfterDegradation = 0.0,
        public readonly float $pvProductionKwh = 0.0,
        public readonly float $pvGrossRevenue = 0.0,
        public readonly float $pvDirectMarketingCosts = 0.0,
        public readonly float $pvNetRevenue = 0.0,
        public readonly float $pvDegradationFactor = 1.0,
        public readonly float $pvPriceFactor = 1.0,
        public readonly bool $manualPvRevenueOverrideUsed = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'year' => $this->year,
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
            'batteryCapexInvestor' => $this->batteryCapexInvestor,
            'batteryReplacementCapexInvestor' => $this->batteryReplacementCapexInvestor,
            'batteryDegradationFactor' => $this->batteryDegradationFactor,
            'batteryRevenueBeforeDegradation' => $this->batteryRevenueBeforeDegradation,
            'batteryRevenueAfterDegradation' => $this->batteryRevenueAfterDegradation,
            'pvProductionKwh' => $this->pvProductionKwh,
            'pvGrossRevenue' => $this->pvGrossRevenue,
            'pvDirectMarketingCosts' => $this->pvDirectMarketingCosts,
            'pvNetRevenue' => $this->pvNetRevenue,
            'pvDegradationFactor' => $this->pvDegradationFactor,
            'pvPriceFactor' => $this->pvPriceFactor,
            'manualPvRevenueOverrideUsed' => $this->manualPvRevenueOverrideUsed,
            'months' => array_map(static fn(MonthResult $month): array => $month->toArray(), $this->months),
        ];
    }
}
