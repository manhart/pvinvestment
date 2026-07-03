<?php
declare(strict_types=1);

namespace pvinvestment\tests\Unit;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use pvinvestment\classes\Calculators\DepreciationCalculator;
use pvinvestment\classes\Calculators\TaxCalculator;
use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\FinancingAssumptions;
use pvinvestment\classes\Domain\PvAssumptions;
use pvinvestment\classes\Domain\Tax\TaxAsset;
use pvinvestment\classes\Domain\Tax\TaxAssetLedger;
use pvinvestment\classes\Domain\TaxAssumptions;

final class DepreciationCalculatorTest extends TestCase
{
    public function testLinearDepreciationOverFullUsefulLife(): void
    {
        $schedule = $this->calculator()->calculate(
            asset: $this->asset(acquisitionCost: 120000.0, usefulLifeMonths: 120),
            startYear: 2026,
            endYear: 2035,
        );

        self::assertSame(12000.0, $schedule->year(2026)->regularDepreciation);
        self::assertSame(108000.0, $schedule->year(2026)->closingBookValue);
        self::assertSame(12000.0, $schedule->year(2035)->openingBookValue);
        self::assertSame(12000.0, $schedule->year(2035)->regularDepreciation);
        self::assertSame(0.0, $schedule->year(2035)->closingBookValue);
    }

    public function testLinearDepreciationStartingInSeptember(): void
    {
        $schedule = $this->calculator()->calculate(
            asset: $this->asset(
                acquisitionCost: 120000.0,
                depreciationStart: '2026-09-01',
                usefulLifeMonths: 120,
            ),
            startYear: 2026,
            endYear: 2027,
        );

        self::assertSame(4, $schedule->year(2026)->depreciationMonths);
        self::assertSame(4000.0, $schedule->year(2026)->regularDepreciation);
        self::assertSame(116000.0, $schedule->year(2026)->closingBookValue);
        self::assertSame(12000.0, $schedule->year(2027)->regularDepreciation);
    }

    public function testDecliningBalanceDepreciationStartingInSeptember(): void
    {
        $schedule = $this->calculator()->calculate(
            asset: $this->asset(
                acquisitionCost: 120000.0,
                depreciationStart: '2026-09-01',
                usefulLifeMonths: 120,
                depreciationMethod: TaxAsset::DEPRECIATION_DECLINING_BALANCE,
                decliningBalanceRate: 0.20,
                switchToLinear: TaxAsset::SWITCH_TO_LINEAR_NO,
            ),
            startYear: 2026,
            endYear: 2027,
        );

        self::assertSame(4, $schedule->year(2026)->depreciationMonths);
        self::assertSame(8000.0, $schedule->year(2026)->regularDepreciation);
        self::assertSame(112000.0, $schedule->year(2026)->closingBookValue);
        self::assertSame(22400.0, $schedule->year(2027)->regularDepreciation);
        self::assertSame(TaxAsset::DEPRECIATION_DECLINING_BALANCE, $schedule->year(2027)->methodUsed);
    }

    public function testDecliningBalanceDepreciationProgressesOverSeveralYears(): void
    {
        $schedule = $this->calculator()->calculate(
            asset: $this->asset(
                acquisitionCost: 100000.0,
                usefulLifeMonths: 120,
                depreciationMethod: TaxAsset::DEPRECIATION_DECLINING_BALANCE,
                decliningBalanceRate: 0.20,
                switchToLinear: TaxAsset::SWITCH_TO_LINEAR_NO,
            ),
            startYear: 2026,
            endYear: 2028,
        );

        self::assertSame(20000.0, $schedule->year(2026)->regularDepreciation);
        self::assertSame(80000.0, $schedule->year(2026)->closingBookValue);
        self::assertSame(16000.0, $schedule->year(2027)->regularDepreciation);
        self::assertSame(64000.0, $schedule->year(2027)->closingBookValue);
        self::assertSame(12800.0, $schedule->year(2028)->regularDepreciation);
    }

    public function testDecliningBalanceSwitchesAutomaticallyToLinearWhenLinearIsHigher(): void
    {
        $schedule = $this->calculator()->calculate(
            asset: $this->asset(
                acquisitionCost: 100000.0,
                usefulLifeMonths: 60,
                depreciationMethod: TaxAsset::DEPRECIATION_DECLINING_BALANCE,
                decliningBalanceRate: 0.20,
                switchToLinear: TaxAsset::SWITCH_TO_LINEAR_AUTO,
            ),
            startYear: 2026,
            endYear: 2028,
        );

        self::assertSame(TaxAsset::DEPRECIATION_DECLINING_BALANCE, $schedule->year(2026)->methodUsed);
        self::assertSame(20000.0, $schedule->year(2026)->regularDepreciation);
        self::assertSame(TaxAsset::DEPRECIATION_LINEAR, $schedule->year(2027)->methodUsed);
        self::assertSame(20000.0, $schedule->year(2027)->regularDepreciation);
        self::assertSame(TaxAsset::DEPRECIATION_LINEAR, $schedule->year(2028)->methodUsed);
        self::assertSame(20000.0, $schedule->year(2028)->regularDepreciation);
    }

    public function testSpecialDepreciationInFirstYear(): void
    {
        $schedule = $this->calculator()->calculate(
            asset: $this->asset(
                acquisitionCost: 100000.0,
                usefulLifeMonths: 120,
                specialDepreciationEnabled: true,
                specialDepreciationTotalRate: 0.20,
                specialDepreciationDistributionByYear: [2026 => 0.20],
            ),
            startYear: 2026,
            endYear: 2026,
        );

        self::assertSame(10000.0, $schedule->year(2026)->regularDepreciation);
        self::assertSame(20000.0, $schedule->year(2026)->specialDepreciation);
        self::assertSame(70000.0, $schedule->year(2026)->closingBookValue);
    }

    public function testSpecialDepreciationDistributedOverSeveralYears(): void
    {
        $schedule = $this->calculator()->calculate(
            asset: $this->asset(
                acquisitionCost: 100000.0,
                usefulLifeMonths: 120,
                specialDepreciationEnabled: true,
                specialDepreciationTotalRate: 0.20,
                specialDepreciationDistributionByYear: [2026 => 0.10, 2027 => 0.10],
            ),
            startYear: 2026,
            endYear: 2027,
        );

        self::assertSame(10000.0, $schedule->year(2026)->regularDepreciation);
        self::assertSame(10000.0, $schedule->year(2026)->specialDepreciation);
        self::assertSame(80000.0, $schedule->year(2026)->closingBookValue);
        self::assertEqualsWithDelta(8888.888889, $schedule->year(2027)->regularDepreciation, 0.000001);
        self::assertSame(10000.0, $schedule->year(2027)->specialDepreciation);
        self::assertEqualsWithDelta(61111.111111, $schedule->year(2027)->closingBookValue, 0.000001);
    }

    public function testIabReductionReducesDepreciationBasis(): void
    {
        $schedule = $this->calculator()->calculate(
            asset: $this->asset(
                acquisitionCost: 100000.0,
                capitalizableAncillaryCosts: 10000.0,
                usefulLifeMonths: 120,
                iabReductionAmount: 40000.0,
            ),
            startYear: 2026,
            endYear: 2026,
        );

        self::assertSame(70000.0, $schedule->asset->depreciationBasis());
        self::assertSame(7000.0, $schedule->year(2026)->regularDepreciation);
    }

    public function testCapitalizableAncillaryCostsIncreaseDepreciationBasis(): void
    {
        $schedule = $this->calculator()->calculate(
            asset: $this->asset(
                acquisitionCost: 100000.0,
                capitalizableAncillaryCosts: 10000.0,
                usefulLifeMonths: 120,
            ),
            startYear: 2026,
            endYear: 2026,
        );

        self::assertSame(110000.0, $schedule->asset->depreciationBasis());
        self::assertSame(11000.0, $schedule->year(2026)->regularDepreciation);
    }

    public function testTaxCalculatorKeepsImmediatelyDeductibleCostsOutsideDepreciationBasis(): void
    {
        $result = (new TaxCalculator())->calculate(
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2026,
                incomeTaxRate: 0.30,
                acquisitionCost: 100000.0,
                immediatelyDeductibleCosts: 10000.0,
                linearDepreciationRate: 0.10,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 20000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(100000.0, $result->depreciationBasis);
        self::assertSame(10000.0, $result->regularDepreciation);
        self::assertSame(0.0, $result->taxableIncome);
    }

    public function testTaxAssetLedgerAggregatesPvAssetAndBatteryAsset(): void
    {
        $ledger = new TaxAssetLedger([
            $this->asset(assetName: 'PV-Anlage', acquisitionCost: 100000.0, usefulLifeMonths: 120),
            $this->asset(assetName: 'Batteriespeicher', acquisitionCost: 50000.0, usefulLifeMonths: 60),
        ]);

        $year = $ledger->depreciationForYear(2026, $this->calculator());

        self::assertSame(150000.0, $year->openingBookValue);
        self::assertSame(20000.0, $year->regularDepreciation);
        self::assertSame(130000.0, $year->closingBookValue);
    }

    public function testBatteryReplacementInvestmentIsModeledAsSecondAsset(): void
    {
        $ledger = new TaxAssetLedger([
            $this->asset(assetName: 'Batteriespeicher 1', acquisitionCost: 50000.0, usefulLifeMonths: 60),
            $this->asset(
                assetName: 'Batteriespeicher Ersatz',
                acquisitionCost: 30000.0,
                acquisitionDate: '2028-01-01',
                depreciationStart: '2028-01-01',
                usefulLifeMonths: 60,
            ),
        ]);

        $calculator = $this->calculator();
        $year2027 = $ledger->depreciationForYear(2027, $calculator);
        $year2028 = $ledger->depreciationForYear(2028, $calculator);

        self::assertSame(40000.0, $year2027->openingBookValue);
        self::assertSame(10000.0, $year2027->regularDepreciation);
        self::assertSame(30000.0, $year2027->closingBookValue);
        self::assertSame(60000.0, $year2028->openingBookValue);
        self::assertSame(16000.0, $year2028->regularDepreciation);
        self::assertSame(44000.0, $year2028->closingBookValue);
    }

    public function testTaxCalculatorCanUseTaxAssetLedgerForMultipleAssets(): void
    {
        $result = (new TaxCalculator())->calculate(
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2026,
                incomeTaxRate: 0.30,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 50000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
            taxAssetLedger: new TaxAssetLedger([
                $this->asset(assetName: 'PV-Anlage', acquisitionCost: 100000.0, usefulLifeMonths: 120),
                $this->asset(assetName: 'Batteriespeicher', acquisitionCost: 50000.0, usefulLifeMonths: 60),
            ]),
        );

        self::assertSame(150000.0, $result->capitalizableAcquisitionCosts);
        self::assertSame(150000.0, $result->depreciationBasis);
        self::assertSame(20000.0, $result->regularDepreciation);
        self::assertSame(30000.0, $result->taxableIncome);
    }

    /**
     * @param array<int, float> $specialDepreciationDistributionByYear
     */
    private function asset(
        string $assetName = 'PV-Anlage',
        float $acquisitionCost = 100000.0,
        float $capitalizableAncillaryCosts = 0.0,
        string $acquisitionDate = '2026-01-01',
        string $depreciationStart = '2026-01-01',
        int $usefulLifeMonths = 120,
        string $depreciationMethod = TaxAsset::DEPRECIATION_LINEAR,
        float $decliningBalanceRate = 0.0,
        string $switchToLinear = TaxAsset::SWITCH_TO_LINEAR_AUTO,
        float $iabReductionAmount = 0.0,
        bool $specialDepreciationEnabled = false,
        float $specialDepreciationTotalRate = 0.0,
        array $specialDepreciationDistributionByYear = [],
    ): TaxAsset {
        return new TaxAsset(
            assetName: $assetName,
            acquisitionCost: $acquisitionCost,
            capitalizableAncillaryCosts: $capitalizableAncillaryCosts,
            acquisitionDate: new DateTimeImmutable($acquisitionDate),
            depreciationStart: new DateTimeImmutable($depreciationStart),
            usefulLifeMonths: $usefulLifeMonths,
            depreciationMethod: $depreciationMethod,
            decliningBalanceRate: $decliningBalanceRate,
            switchToLinear: $switchToLinear,
            iabReductionAmount: $iabReductionAmount,
            specialDepreciationEnabled: $specialDepreciationEnabled,
            specialDepreciationTotalRate: $specialDepreciationTotalRate,
            specialDepreciationDistributionByYear: $specialDepreciationDistributionByYear,
        );
    }

    private function calculator(): DepreciationCalculator
    {
        return new DepreciationCalculator();
    }
}
