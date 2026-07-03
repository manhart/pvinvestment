<?php
declare(strict_types=1);

namespace pvinvestment\tests\Unit;

use PHPUnit\Framework\TestCase;
use pvinvestment\classes\Calculators\ScenarioCalculator;
use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\FinancingAssumptions;
use pvinvestment\classes\Domain\PvAssumptions;
use pvinvestment\classes\Domain\RevenueSharingModel;
use pvinvestment\classes\Domain\SavingsPlanAssumptions;
use pvinvestment\classes\Domain\Scenario\ScenarioInput;
use pvinvestment\classes\Domain\TaxAssumptions;

final class ScenarioCalculatorTest extends TestCase
{
    public function testFullOwnershipComparedToProfitSharingSixtyFiveThirtyFive(): void
    {
        $comparison = $this->calculator()->compare([
            $this->scenario(
                id: 'full',
                batteryModel: BatteryModel::fullOwnership(annualRevenue: 10000.0, annualOperatingCosts: 1000.0),
                revenueSharingModel: RevenueSharingModel::fullInvestorOwnership(),
                durationYears: 2,
            ),
            $this->scenario(
                id: 'share_65_35',
                batteryModel: BatteryModel::profitSharing(
                    annualRevenue: 10000.0,
                    annualOperatingCosts: 1000.0,
                    sharingModel: RevenueSharingModel::profitSharing(0.65, 0.35),
                ),
                revenueSharingModel: RevenueSharingModel::profitSharing(0.65, 0.35),
                durationYears: 2,
            ),
        ]);

        $base = $comparison->rowFor('full');
        $profitSharing = $comparison->rowFor('share_65_35');

        self::assertSame(38000.0, $base['cumulativeInvestorCashflow']);
        self::assertSame(20000.0, $base['cumulativeInvestorBatteryRevenue']);
        self::assertSame(31000.0, $profitSharing['cumulativeInvestorCashflow']);
        self::assertSame(13000.0, $profitSharing['cumulativeInvestorBatteryRevenue']);
        self::assertSame(-7000.0, $profitSharing['differenceToBase']['totalInvestorResult']);
        self::assertSame(2026, $base['breakEvenYear']);
    }

    public function testFullOwnershipComparedToProfitSharingSixtyFiveThirtyFiveOnGrossRevenue(): void
    {
        $comparison = $this->calculator()->compare([
            $this->scenario(
                id: 'full',
                pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
                batteryModel: BatteryModel::fullOwnership(
                    annualRevenue: 10000.0,
                    annualOperatingCosts: 1000.0,
                    marketAccessFee: 200.0,
                    optimizerFee: 300.0,
                ),
            ),
            $this->scenario(
                id: 'share_65_35_gross',
                pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
                batteryModel: BatteryModel::profitSharing(
                    annualRevenue: 10000.0,
                    annualOperatingCosts: 1000.0,
                    sharingModel: RevenueSharingModel::profitSharing(
                        investorRevenueShare: 0.65,
                        operatorRevenueShare: 0.35,
                        sharingBase: RevenueSharingModel::SHARING_BASE_GROSS_REVENUE,
                    ),
                    marketAccessFee: 200.0,
                    optimizerFee: 300.0,
                ),
            ),
        ]);

        $base = $comparison->rowFor('full');
        $profitSharing = $comparison->rowFor('share_65_35_gross');

        self::assertSame(8500.0, $base['cumulativeInvestorCashflow']);
        self::assertSame(10000.0, $base['cumulativeInvestorBatteryRevenue']);
        self::assertSame(5000.0, $profitSharing['cumulativeInvestorCashflow']);
        self::assertSame(6500.0, $profitSharing['cumulativeInvestorBatteryRevenue']);
        self::assertSame(-3500.0, $profitSharing['differenceToBase']['cumulativeInvestorCashflow']);
    }

    public function testProfitSharingGrossRevenueComparedToNetRevenue(): void
    {
        $comparison = $this->calculator()->compare([
            $this->scenario(
                id: 'share_65_35_gross',
                pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
                batteryModel: $this->profitSharingBattery(RevenueSharingModel::SHARING_BASE_GROSS_REVENUE),
            ),
            $this->scenario(
                id: 'share_65_35_net_revenue',
                pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
                batteryModel: $this->profitSharingBattery(RevenueSharingModel::SHARING_BASE_NET_REVENUE),
            ),
        ]);

        $netRevenue = $comparison->rowFor('share_65_35_net_revenue');

        self::assertSame(5000.0, $comparison->rowFor('share_65_35_gross')['cumulativeInvestorCashflow']);
        self::assertSame(5175.0, $netRevenue['cumulativeInvestorCashflow']);
        self::assertSame(-325.0, $netRevenue['differenceToBase']['cumulativeInvestorBatteryRevenue']);
        self::assertSame(175.0, $netRevenue['differenceToBase']['totalInvestorResult']);
    }

    public function testProfitSharingNetRevenueComparedToNetMargin(): void
    {
        $comparison = $this->calculator()->compare([
            $this->scenario(
                id: 'share_65_35_net_revenue',
                pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
                batteryModel: $this->profitSharingBattery(RevenueSharingModel::SHARING_BASE_NET_REVENUE),
            ),
            $this->scenario(
                id: 'share_65_35_net_margin',
                pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
                batteryModel: $this->profitSharingBattery(RevenueSharingModel::SHARING_BASE_NET_MARGIN),
            ),
        ]);

        $netMargin = $comparison->rowFor('share_65_35_net_margin');

        self::assertSame(5175.0, $comparison->rowFor('share_65_35_net_revenue')['cumulativeInvestorCashflow']);
        self::assertSame(5525.0, $netMargin['cumulativeInvestorCashflow']);
        self::assertSame(-650.0, $netMargin['differenceToBase']['cumulativeInvestorBatteryRevenue']);
        self::assertSame(350.0, $netMargin['differenceToBase']['totalInvestorResult']);
    }

    public function testProfitSharingSixtyFiveThirtyFiveComparedToSeventyThirty(): void
    {
        $comparison = $this->calculator()->compare([
            $this->scenario(
                id: 'share_65_35',
                batteryModel: BatteryModel::profitSharing(
                    annualRevenue: 10000.0,
                    annualOperatingCosts: 1000.0,
                    sharingModel: RevenueSharingModel::profitSharing(0.65, 0.35),
                ),
                revenueSharingModel: RevenueSharingModel::profitSharing(0.65, 0.35),
            ),
            $this->scenario(
                id: 'share_70_30',
                batteryModel: BatteryModel::profitSharing(
                    annualRevenue: 10000.0,
                    annualOperatingCosts: 1000.0,
                    sharingModel: RevenueSharingModel::profitSharing(0.70, 0.30),
                ),
                revenueSharingModel: RevenueSharingModel::profitSharing(0.70, 0.30),
            ),
        ]);

        $seventyThirty = $comparison->rowFor('share_70_30');

        self::assertSame(16000.0, $seventyThirty['cumulativeInvestorCashflow']);
        self::assertSame(7000.0, $seventyThirty['cumulativeInvestorBatteryRevenue']);
        self::assertSame(500.0, $seventyThirty['differenceToBase']['cumulativeInvestorCashflow']);
        self::assertSame(500.0, $seventyThirty['differenceToBase']['cumulativeInvestorBatteryRevenue']);
    }

    public function testStartingCapitalZeroComparedToConfiguredStartingCapital(): void
    {
        $comparison = $this->calculator()->compare([
            $this->scenario(
                id: 'start_zero',
                pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
                batteryModel: BatteryModel::none(),
                savingsPlanAssumptions: new SavingsPlanAssumptions(startingCapital: 0.0),
                durationYears: 2,
            ),
            $this->scenario(
                id: 'start_custom',
                pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
                batteryModel: BatteryModel::none(),
                savingsPlanAssumptions: new SavingsPlanAssumptions(startingCapital: 5000.0),
                durationYears: 2,
            ),
        ]);

        $custom = $comparison->rowFor('start_custom');

        self::assertSame(5000.0, $custom['savingsPlanEndingCapital']);
        self::assertSame(5000.0, $custom['totalInvestorResult']);
        self::assertSame(5000.0, $custom['differenceToBase']['savingsPlanEndingCapital']);
        self::assertSame(5000.0, $custom['differenceToBase']['totalInvestorResult']);
    }

    public function testTaxRateThirtyFivePercentComparedToDifferentTaxRate(): void
    {
        $comparison = $this->calculator()->compare([
            $this->scenario(
                id: 'tax_35',
                pvAssumptions: new PvAssumptions(annualRevenue: 10000.0),
                batteryModel: BatteryModel::none(),
                taxAssumptions: new TaxAssumptions(
                    calculationYear: 2026,
                    incomeTaxRate: 0.35,
                ),
            ),
            $this->scenario(
                id: 'tax_20',
                pvAssumptions: new PvAssumptions(annualRevenue: 10000.0),
                batteryModel: BatteryModel::none(),
                taxAssumptions: new TaxAssumptions(
                    calculationYear: 2026,
                    incomeTaxRate: 0.20,
                ),
            ),
        ]);

        $tax35 = $comparison->rowFor('tax_35');
        $tax20 = $comparison->rowFor('tax_20');

        self::assertSame(3500.0, $tax35['cumulativeTaxCashflow']);
        self::assertSame(6500.0, $tax35['cumulativeInvestorCashflow']);
        self::assertSame(2000.0, $tax20['cumulativeTaxCashflow']);
        self::assertSame(8000.0, $tax20['cumulativeInvestorCashflow']);
        self::assertSame(-1500.0, $tax20['differenceToBase']['cumulativeTaxCashflow']);
        self::assertSame(1500.0, $tax20['differenceToBase']['totalInvestorResult']);
    }

    private function scenario(
        string $id,
        ?PvAssumptions $pvAssumptions = null,
        ?BatteryModel $batteryModel = null,
        ?RevenueSharingModel $revenueSharingModel = null,
        ?FinancingAssumptions $financingAssumptions = null,
        ?TaxAssumptions $taxAssumptions = null,
        ?SavingsPlanAssumptions $savingsPlanAssumptions = null,
        int $durationYears = 1,
    ): ScenarioInput {
        $batteryModel ??= BatteryModel::none();
        $revenueSharingModel ??= $batteryModel->sharingModel;

        return new ScenarioInput(
            id: $id,
            description: $id,
            pvAssumptions: $pvAssumptions ?? new PvAssumptions(annualRevenue: 10000.0),
            batteryModel: $batteryModel,
            revenueSharingModel: $revenueSharingModel,
            financingAssumptions: $financingAssumptions ?? new FinancingAssumptions(),
            taxAssumptions: $taxAssumptions ?? new TaxAssumptions(calculationYear: 2026),
            savingsPlanAssumptions: $savingsPlanAssumptions ?? new SavingsPlanAssumptions(),
            durationYears: $durationYears,
        );
    }

    private function profitSharingBattery(string $sharingBase): BatteryModel
    {
        return BatteryModel::profitSharing(
            annualRevenue: 10000.0,
            annualOperatingCosts: 1000.0,
            sharingModel: RevenueSharingModel::profitSharing(
                investorRevenueShare: 0.65,
                operatorRevenueShare: 0.35,
                sharingBase: $sharingBase,
            ),
            marketAccessFee: 200.0,
            optimizerFee: 300.0,
        );
    }

    private function calculator(): ScenarioCalculator
    {
        return new ScenarioCalculator();
    }
}
