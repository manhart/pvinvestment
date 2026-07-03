<?php
declare(strict_types=1);

namespace pvinvestment\tests\Unit;

use PHPUnit\Framework\TestCase;
use pvinvestment\classes\Calculators\AnnualInvestorCashflowCalculator;
use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\FinancingAssumptions;
use pvinvestment\classes\Domain\PvAssumptions;
use pvinvestment\classes\Domain\RevenueSharingModel;
use pvinvestment\classes\Domain\SavingsPlanAssumptions;
use pvinvestment\classes\Domain\TaxAssumptions;

final class AnnualInvestorCashflowCalculatorTest extends TestCase
{
    public function testBatteryFullOwnershipAllocatesRevenueAndCostsToInvestor(): void
    {
        $result = $this->calculator()->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 10000.0, annualOperatingCosts: 1000.0),
            batteryModel: BatteryModel::fullOwnership(
                annualRevenue: 4000.0,
                annualOperatingCosts: 500.0,
                marketAccessFee: 100.0,
                optimizerFee: 50.0,
                batteryCapex: 12000.0,
            ),
            financingAssumptions: new FinancingAssumptions(),
            taxAssumptions: new TaxAssumptions(),
            savingsPlanAssumptions: new SavingsPlanAssumptions(),
        );

        self::assertSame(4000.0, $result->investorBatteryRevenue);
        self::assertSame(0.0, $result->operatorBatteryRevenue);
        self::assertSame(650.0, $result->investorBatteryCosts);
        self::assertSame(0.0, $result->operatorBatteryCosts);
        self::assertSame(4000.0, $result->batteryGrossRevenue);
        self::assertSame(3850.0, $result->batteryNetRevenue);
        self::assertSame(3350.0, $result->batteryNetMargin);
        self::assertSame(12000.0, $result->investorBatteryCapex);
        self::assertSame(0.0, $result->operatorBatteryCapex);
        self::assertSame(RevenueSharingModel::SHARING_BASE_GROSS_REVENUE, $result->batterySharingBase);
        self::assertSame(12350.0, $result->annualInvestorCashflow);
    }

    public function testBatteryProfitSharingAllocatesRevenueByInvestorAndOperatorShares(): void
    {
        $result = $this->calculator()->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::profitSharing(
                annualRevenue: 10000.0,
                annualOperatingCosts: 1000.0,
                sharingModel: RevenueSharingModel::profitSharing(
                    investorRevenueShare: 0.35,
                    operatorRevenueShare: 0.65,
                ),
            ),
            financingAssumptions: new FinancingAssumptions(),
            taxAssumptions: new TaxAssumptions(),
            savingsPlanAssumptions: new SavingsPlanAssumptions(),
        );

        self::assertSame(3500.0, $result->investorBatteryRevenue);
        self::assertSame(6500.0, $result->operatorBatteryRevenue);
        self::assertSame(1000.0, $result->investorBatteryCosts);
        self::assertSame(0.0, $result->operatorBatteryCosts);
        self::assertSame(2500.0, $result->annualInvestorCashflow);
    }

    public function testProfitSharingSixtyFiveThirtyFiveOnGrossRevenue(): void
    {
        $result = $this->calculator()->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::profitSharing(
                annualRevenue: 10000.0,
                annualOperatingCosts: 1000.0,
                sharingModel: RevenueSharingModel::profitSharing(
                    investorRevenueShare: 0.65,
                    operatorRevenueShare: 0.35,
                    sharingBase: RevenueSharingModel::SHARING_BASE_GROSS_REVENUE,
                    investorCostShare: 0.65,
                    operatorCostShare: 0.35,
                ),
                marketAccessFee: 200.0,
                optimizerFee: 300.0,
            ),
            financingAssumptions: new FinancingAssumptions(),
            taxAssumptions: new TaxAssumptions(),
            savingsPlanAssumptions: new SavingsPlanAssumptions(),
        );

        self::assertSame(6500.0, $result->investorBatteryRevenue);
        self::assertSame(3500.0, $result->operatorBatteryRevenue);
        self::assertSame(975.0, $result->investorBatteryCosts);
        self::assertSame(525.0, $result->operatorBatteryCosts);
        self::assertSame(5525.0, $result->annualInvestorCashflow);
    }

    public function testProfitSharingSixtyFiveThirtyFiveOnNetRevenue(): void
    {
        $result = $this->calculator()->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::profitSharing(
                annualRevenue: 10000.0,
                annualOperatingCosts: 1000.0,
                sharingModel: RevenueSharingModel::profitSharing(
                    investorRevenueShare: 0.65,
                    operatorRevenueShare: 0.35,
                    sharingBase: RevenueSharingModel::SHARING_BASE_NET_REVENUE,
                    investorCostShare: 0.65,
                    operatorCostShare: 0.35,
                ),
                marketAccessFee: 200.0,
                optimizerFee: 300.0,
            ),
            financingAssumptions: new FinancingAssumptions(),
            taxAssumptions: new TaxAssumptions(),
            savingsPlanAssumptions: new SavingsPlanAssumptions(),
        );

        self::assertSame(9500.0, $result->batteryNetRevenue);
        self::assertSame(6175.0, $result->investorBatteryRevenue);
        self::assertSame(3325.0, $result->operatorBatteryRevenue);
        self::assertSame(650.0, $result->investorBatteryCosts);
        self::assertSame(350.0, $result->operatorBatteryCosts);
        self::assertSame(5525.0, $result->annualInvestorCashflow);
    }

    public function testProfitSharingSixtyFiveThirtyFiveOnNetMarginDoesNotDeductIncludedCostsAgain(): void
    {
        $result = $this->calculator()->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::profitSharing(
                annualRevenue: 10000.0,
                annualOperatingCosts: 1000.0,
                sharingModel: RevenueSharingModel::profitSharing(
                    investorRevenueShare: 0.65,
                    operatorRevenueShare: 0.35,
                    sharingBase: RevenueSharingModel::SHARING_BASE_NET_MARGIN,
                    investorCostShare: 0.65,
                    operatorCostShare: 0.35,
                ),
                marketAccessFee: 200.0,
                optimizerFee: 300.0,
            ),
            financingAssumptions: new FinancingAssumptions(),
            taxAssumptions: new TaxAssumptions(),
            savingsPlanAssumptions: new SavingsPlanAssumptions(),
        );

        self::assertSame(8500.0, $result->batteryNetMargin);
        self::assertSame(5525.0, $result->investorBatteryRevenue);
        self::assertSame(2975.0, $result->operatorBatteryRevenue);
        self::assertSame(0.0, $result->investorBatteryCosts);
        self::assertSame(0.0, $result->operatorBatteryCosts);
        self::assertSame(5525.0, $result->annualInvestorCashflow);
    }

    public function testNegativeNetMarginIsNotCappedToZero(): void
    {
        $result = $this->calculator()->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::profitSharing(
                annualRevenue: 1000.0,
                annualOperatingCosts: 600.0,
                sharingModel: RevenueSharingModel::profitSharing(
                    investorRevenueShare: 0.65,
                    operatorRevenueShare: 0.35,
                    sharingBase: RevenueSharingModel::SHARING_BASE_NET_MARGIN,
                ),
                marketAccessFee: 300.0,
                optimizerFee: 300.0,
            ),
            financingAssumptions: new FinancingAssumptions(),
            taxAssumptions: new TaxAssumptions(),
            savingsPlanAssumptions: new SavingsPlanAssumptions(),
        );

        self::assertSame(-200.0, $result->batteryNetMargin);
        self::assertSame(-130.0, $result->investorBatteryRevenue);
        self::assertSame(-70.0, $result->operatorBatteryRevenue);
        self::assertSame(0.0, $result->investorBatteryCosts);
        self::assertSame(-130.0, $result->annualInvestorCashflow);
    }

    public function testSavingsPlanStartingCapitalIsConfigurable(): void
    {
        $result = $this->calculator()->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
            taxAssumptions: new TaxAssumptions(),
            savingsPlanAssumptions: new SavingsPlanAssumptions(startingCapital: 25000.0),
        );

        self::assertSame(25000.0, $result->savingsPlanStartingCapital);
        self::assertSame(25000.0, $result->savingsPlanEndingCapital);
    }

    public function testSimpleAnnualInvestorCashflowIncludesPvBatteryFinancingAndTax(): void
    {
        $result = $this->calculator()->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 20000.0, annualOperatingCosts: 3000.0),
            batteryModel: BatteryModel::fullOwnership(annualRevenue: 5000.0, annualOperatingCosts: 1000.0),
            financingAssumptions: new FinancingAssumptions(annualInterest: 2500.0, annualRepayment: 4500.0),
            taxAssumptions: new TaxAssumptions(annualTaxPayment: 2000.0),
            savingsPlanAssumptions: new SavingsPlanAssumptions(
                startingCapital: 10000.0,
                monthlyContribution: 100.0,
                annualContribution: 500.0,
                positiveCashflowReinvestmentRate: 0.5,
            ),
        );

        self::assertSame(12000.0, $result->annualInvestorCashflow);
        self::assertSame(1700.0, $result->savingsPlanFixedContribution);
        self::assertSame(6000.0, $result->savingsPlanCashflowContribution);
        self::assertSame(17700.0, $result->savingsPlanEndingCapital);
    }

    private function calculator(): AnnualInvestorCashflowCalculator
    {
        return new AnnualInvestorCashflowCalculator();
    }
}
