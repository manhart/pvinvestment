<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain;

use InvalidArgumentException;

final class PvAssumptions
{
    public function __construct(
        public readonly float $annualRevenue = 0.0,
        public readonly float $annualOperatingCosts = 0.0,
        public readonly float $installedCapacityKwp = 0.0,
        public readonly float $specificYieldKwhPerKwp = 0.0,
        public readonly float $pvDegradationRatePerYear = 0.0,
        public readonly float $pvAvailabilityRate = 1.0,
        public readonly float $pvCurtailmentRate = 0.0,
        public readonly float $electricityPriceCentsPerKwh = 0.0,
        public readonly float $electricityPriceEscalationRatePerYear = 0.0,
        public readonly float $directMarketingCostCentsPerKwh = 0.0,
        public readonly float $otherRevenueDeductionRate = 0.0,
        public readonly ?float $manualPvAnnualRevenueOverride = null,
    ) {
        if($annualRevenue < 0.0) {
            throw new InvalidArgumentException('Annual PV revenue must not be negative.');
        }
        if($annualOperatingCosts < 0.0) {
            throw new InvalidArgumentException('Annual PV operating costs must not be negative.');
        }
        foreach([
            'installedCapacityKwp' => $installedCapacityKwp,
            'specificYieldKwhPerKwp' => $specificYieldKwhPerKwp,
            'electricityPriceCentsPerKwh' => $electricityPriceCentsPerKwh,
            'directMarketingCostCentsPerKwh' => $directMarketingCostCentsPerKwh,
        ] as $label => $value) {
            if($value < 0.0) {
                throw new InvalidArgumentException($label.' must not be negative.');
            }
        }
        foreach([
            'pvDegradationRatePerYear' => $pvDegradationRatePerYear,
            'pvAvailabilityRate' => $pvAvailabilityRate,
            'pvCurtailmentRate' => $pvCurtailmentRate,
            'otherRevenueDeductionRate' => $otherRevenueDeductionRate,
        ] as $label => $value) {
            if($value < 0.0 || $value > 1.0) {
                throw new InvalidArgumentException($label.' must be between 0 and 1.');
            }
        }
        if($electricityPriceEscalationRatePerYear < -1.0) {
            throw new InvalidArgumentException('Electricity price escalation rate must not be below -1.');
        }
        if($manualPvAnnualRevenueOverride !== null && $manualPvAnnualRevenueOverride < 0.0) {
            throw new InvalidArgumentException('Manual PV annual revenue override must not be negative.');
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

    public function withAnnualRevenue(float $annualRevenue): self
    {
        return new self(
            annualRevenue: $annualRevenue,
            annualOperatingCosts: $this->annualOperatingCosts,
            installedCapacityKwp: $this->installedCapacityKwp,
            specificYieldKwhPerKwp: $this->specificYieldKwhPerKwp,
            pvDegradationRatePerYear: $this->pvDegradationRatePerYear,
            pvAvailabilityRate: $this->pvAvailabilityRate,
            pvCurtailmentRate: $this->pvCurtailmentRate,
            electricityPriceCentsPerKwh: $this->electricityPriceCentsPerKwh,
            electricityPriceEscalationRatePerYear: $this->electricityPriceEscalationRatePerYear,
            directMarketingCostCentsPerKwh: $this->directMarketingCostCentsPerKwh,
            otherRevenueDeductionRate: $this->otherRevenueDeductionRate,
            manualPvAnnualRevenueOverride: $this->manualPvAnnualRevenueOverride,
        );
    }
}
