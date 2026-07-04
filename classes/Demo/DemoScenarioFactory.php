<?php
declare(strict_types=1);

namespace pvinvestment\classes\Demo;

use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\FinancingAssumptions;
use pvinvestment\classes\Domain\ProjectTimingAssumptions;
use pvinvestment\classes\Domain\PvAssumptions;
use pvinvestment\classes\Domain\RevenueSharingModel;
use pvinvestment\classes\Domain\SavingsPlanAssumptions;
use pvinvestment\classes\Domain\Scenario\ScenarioInput;
use pvinvestment\classes\Domain\Tax\TaxLossHandlingStrategy;
use pvinvestment\classes\Domain\TaxAssumptions;

final class DemoScenarioFactory
{
    public static function fullOwnership(): ScenarioInput
    {
        return self::scenario(
            id: 'demo_full_ownership',
            description: 'Demo Batterie Vollerwerb',
            batteryModel: BatteryModel::fullOwnership(
                annualRevenue: 12000.0,
                annualOperatingCosts: 1000.0,
                marketAccessFee: 400.0,
                optimizerFee: 600.0,
                batteryCapex: 30000.0,
                batteryDegradationRatePerYear: 0.02,
                batteryReplacementEnabled: true,
                batteryReplacementYear: 2029,
                batteryReplacementMonth: 7,
                batteryReplacementCost: 18000.0,
            ),
        );
    }

    public static function profitSharingGrossRevenue(): ScenarioInput
    {
        return self::scenario(
            id: 'demo_profit_sharing_gross_65_35',
            description: 'Demo Profit-Sharing 65/35 gross_revenue',
            batteryModel: BatteryModel::profitSharing(
                annualRevenue: 12000.0,
                annualOperatingCosts: 1000.0,
                sharingModel: RevenueSharingModel::profitSharing(
                    investorRevenueShare: 0.65,
                    operatorRevenueShare: 0.35,
                    sharingBase: RevenueSharingModel::SHARING_BASE_GROSS_REVENUE,
                    investorCostShare: 0.65,
                    operatorCostShare: 0.35,
                    investorCapexShare: 0.65,
                    operatorCapexShare: 0.35,
                ),
                marketAccessFee: 400.0,
                optimizerFee: 600.0,
                batteryCapex: 30000.0,
                batteryDegradationRatePerYear: 0.02,
                batteryReplacementEnabled: true,
                batteryReplacementYear: 2029,
                batteryReplacementMonth: 7,
                batteryReplacementCost: 18000.0,
                investorReplacementCostShare: 0.65,
                operatorReplacementCostShare: 0.35,
            ),
        );
    }

    public static function profitSharingNetRevenue(): ScenarioInput
    {
        return self::scenario(
            id: 'demo_profit_sharing_net_revenue_65_35',
            description: 'Demo Profit-Sharing 65/35 net_revenue',
            batteryModel: BatteryModel::profitSharing(
                annualRevenue: 12000.0,
                annualOperatingCosts: 1000.0,
                sharingModel: RevenueSharingModel::profitSharing(
                    investorRevenueShare: 0.65,
                    operatorRevenueShare: 0.35,
                    sharingBase: RevenueSharingModel::SHARING_BASE_NET_REVENUE,
                    investorCostShare: 0.65,
                    operatorCostShare: 0.35,
                    investorCapexShare: 0.65,
                    operatorCapexShare: 0.35,
                ),
                marketAccessFee: 400.0,
                optimizerFee: 600.0,
                batteryCapex: 30000.0,
                batteryDegradationRatePerYear: 0.02,
                batteryReplacementEnabled: true,
                batteryReplacementYear: 2029,
                batteryReplacementMonth: 7,
                batteryReplacementCost: 18000.0,
                investorReplacementCostShare: 0.65,
                operatorReplacementCostShare: 0.35,
            ),
        );
    }

    /**
     * @return list<ScenarioInput>
     */
    public static function comparisonScenarios(): array
    {
        return [
            self::fullOwnership(),
            self::profitSharingGrossRevenue(),
            self::profitSharingNetRevenue(),
        ];
    }

    private static function scenario(string $id, string $description, BatteryModel $batteryModel): ScenarioInput
    {
        return new ScenarioInput(
            id: $id,
            description: $description,
            pvAssumptions: new PvAssumptions(
                annualRevenue: 24000.0,
                annualOperatingCosts: 1800.0,
            ),
            batteryModel: $batteryModel,
            revenueSharingModel: $batteryModel->sharingModel,
            financingAssumptions: new FinancingAssumptions(
                annualInterest: 3200.0,
                annualRepayment: 6000.0,
            ),
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2026,
                incomeTaxRate: 0.32,
                acquisitionCost: 220000.0,
                capitalizableAncillaryCosts: 9000.0,
                depreciationStartYear: 2026,
                depreciationStartMonth: 9,
                linearDepreciationRate: 0.05,
                lossHandlingStrategy: TaxLossHandlingStrategy::IMMEDIATE,
            ),
            savingsPlanAssumptions: new SavingsPlanAssumptions(
                startingCapital: 5000.0,
                monthlyContribution: 100.0,
                positiveCashflowReinvestmentRate: 0.15,
            ),
            durationYears: 5,
            timingAssumptions: new ProjectTimingAssumptions(
                calculationYear: 2026,
                investmentYear: 2026,
                investmentMonth: 8,
                depreciationStartYear: 2026,
                depreciationStartMonth: 9,
                eegCommissioningYear: 2026,
                eegCommissioningMonth: 10,
                gridConnectionYear: 2026,
                gridConnectionMonth: 11,
                revenueStartYear: 2026,
                revenueStartMonth: 11,
                interestStartYear: 2026,
                interestStartMonth: 8,
                repaymentStartYear: 2026,
                repaymentStartMonth: 10,
                savingsPlanContributionStartYear: 2026,
                savingsPlanContributionStartMonth: 11,
            ),
        );
    }
}
