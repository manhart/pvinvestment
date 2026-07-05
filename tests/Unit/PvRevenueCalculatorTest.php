<?php
declare(strict_types=1);

namespace pvinvestment\tests\Unit;

use PHPUnit\Framework\TestCase;
use pvinvestment\classes\Calculators\PvRevenueCalculator;
use pvinvestment\classes\Domain\PvAssumptions;

final class PvRevenueCalculatorTest extends TestCase
{
    public function testPvProductionIsCalculatedFromCapacityAndSpecificYield(): void
    {
        $result = $this->calculator()->calculateForYear(
            new PvAssumptions(installedCapacityKwp: 100.0, specificYieldKwhPerKwp: 1000.0),
            year: 2026,
            revenueStartYear: 2026,
        );

        self::assertSame(100000.0, $result->annualProductionKwh);
    }

    public function testPvRevenueIsCalculatedFromProductionAndElectricityPrice(): void
    {
        $result = $this->calculator()->calculateForYear(
            new PvAssumptions(
                installedCapacityKwp: 100.0,
                specificYieldKwhPerKwp: 1000.0,
                electricityPriceCentsPerKwh: 10.0,
            ),
            year: 2026,
            revenueStartYear: 2026,
        );

        self::assertSame(10000.0, $result->grossRevenue);
        self::assertSame(10000.0, $result->netRevenue);
    }

    public function testDirectMarketingCostsReduceNetRevenue(): void
    {
        $result = $this->calculator()->calculateForYear(
            new PvAssumptions(
                installedCapacityKwp: 100.0,
                specificYieldKwhPerKwp: 1000.0,
                electricityPriceCentsPerKwh: 10.0,
                directMarketingCostCentsPerKwh: 1.0,
            ),
            year: 2026,
            revenueStartYear: 2026,
        );

        self::assertSame(1000.0, $result->directMarketingCosts);
        self::assertSame(9000.0, $result->netRevenue);
    }

    public function testDegradationReducesProductionInFollowingYear(): void
    {
        $result = $this->calculator()->calculateForYear(
            new PvAssumptions(
                installedCapacityKwp: 100.0,
                specificYieldKwhPerKwp: 1000.0,
                pvDegradationRatePerYear: 0.02,
            ),
            year: 2027,
            revenueStartYear: 2026,
        );

        self::assertSame(0.98, $result->degradationFactor);
        self::assertSame(98000.0, $result->annualProductionKwh);
    }

    public function testPriceEscalationIncreasesPriceInFollowingYear(): void
    {
        $result = $this->calculator()->calculateForYear(
            new PvAssumptions(
                installedCapacityKwp: 100.0,
                specificYieldKwhPerKwp: 1000.0,
                electricityPriceCentsPerKwh: 10.0,
                electricityPriceEscalationRatePerYear: 0.03,
            ),
            year: 2027,
            revenueStartYear: 2026,
        );

        self::assertSame(1.03, $result->priceFactor);
        self::assertSame(10300.0, $result->grossRevenue);
    }

    public function testDegradationAndPriceEscalationAreAppliedSeparately(): void
    {
        $result = $this->calculator()->calculateForYear(
            new PvAssumptions(
                installedCapacityKwp: 100.0,
                specificYieldKwhPerKwp: 1000.0,
                pvDegradationRatePerYear: 0.02,
                electricityPriceCentsPerKwh: 10.0,
                electricityPriceEscalationRatePerYear: 0.03,
            ),
            year: 2027,
            revenueStartYear: 2026,
        );

        self::assertSame(98000.0, $result->annualProductionKwh);
        self::assertEqualsWithDelta(10094.0, $result->grossRevenue, 0.000001);
    }

    public function testManualOverrideReplacesCalculatedNetRevenue(): void
    {
        $result = $this->calculator()->calculateForYear(
            new PvAssumptions(
                installedCapacityKwp: 100.0,
                specificYieldKwhPerKwp: 1000.0,
                electricityPriceCentsPerKwh: 10.0,
                manualPvAnnualRevenueOverride: 12345.0,
            ),
            year: 2026,
            revenueStartYear: 2026,
        );

        self::assertSame(10000.0, $result->grossRevenue);
        self::assertSame(12345.0, $result->netRevenue);
        self::assertTrue($result->manualOverrideUsed);
    }

    private function calculator(): PvRevenueCalculator
    {
        return new PvRevenueCalculator();
    }
}
