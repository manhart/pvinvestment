<?php
declare(strict_types=1);

namespace pvinvestment\classes\Calculators;

use pvinvestment\classes\Domain\PvAssumptions;
use pvinvestment\classes\Domain\PvRevenueResult;

final class PvRevenueCalculator
{
    public function calculateForYear(PvAssumptions $assumptions, int $year, int $revenueStartYear): PvRevenueResult
    {
        $yearIndex = max(0, $year - $revenueStartYear);
        $degradationFactor = (1.0 - $assumptions->pvDegradationRatePerYear) ** $yearIndex;
        $priceFactor = (1.0 + $assumptions->electricityPriceEscalationRatePerYear) ** $yearIndex;
        $annualProductionKwh = $assumptions->installedCapacityKwp
            * $assumptions->specificYieldKwhPerKwp
            * $degradationFactor
            * $assumptions->pvAvailabilityRate
            * (1.0 - $assumptions->pvCurtailmentRate);
        $effectivePriceEurPerKwh = ($assumptions->electricityPriceCentsPerKwh / 100.0) * $priceFactor;
        $directMarketingCostEurPerKwh = $assumptions->directMarketingCostCentsPerKwh / 100.0;
        $grossRevenue = $annualProductionKwh * $effectivePriceEurPerKwh;
        $directMarketingCosts = $annualProductionKwh * $directMarketingCostEurPerKwh;
        $netRevenue = ($grossRevenue - $directMarketingCosts) * (1.0 - $assumptions->otherRevenueDeductionRate);

        if($assumptions->manualPvAnnualRevenueOverride !== null) {
            return new PvRevenueResult(
                year: $year,
                annualProductionKwh: $annualProductionKwh,
                grossRevenue: $grossRevenue,
                directMarketingCosts: $directMarketingCosts,
                netRevenue: $assumptions->manualPvAnnualRevenueOverride,
                degradationFactor: $degradationFactor,
                priceFactor: $priceFactor,
                manualOverrideUsed: true,
            );
        }

        if($assumptions->annualRevenue > 0.0 && $annualProductionKwh === 0.0) {
            return new PvRevenueResult(
                year: $year,
                annualProductionKwh: 0.0,
                grossRevenue: $assumptions->annualRevenue,
                directMarketingCosts: 0.0,
                netRevenue: $assumptions->annualRevenue,
                degradationFactor: 1.0,
                priceFactor: 1.0,
                manualOverrideUsed: true,
            );
        }

        return new PvRevenueResult(
            year: $year,
            annualProductionKwh: $annualProductionKwh,
            grossRevenue: $grossRevenue,
            directMarketingCosts: $directMarketingCosts,
            netRevenue: $netRevenue,
            degradationFactor: $degradationFactor,
            priceFactor: $priceFactor,
        );
    }
}
