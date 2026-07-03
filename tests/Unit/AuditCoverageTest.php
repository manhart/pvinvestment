<?php
declare(strict_types=1);

namespace pvinvestment\tests\Unit;

use PHPUnit\Framework\TestCase;
use pvinvestment\classes\Calculators\AnnualInvestorCashflowCalculator;
use pvinvestment\classes\Calculators\TaxCalculator;
use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\FinancingAssumptions;
use pvinvestment\classes\Domain\ProjectTimingAssumptions;
use pvinvestment\classes\Domain\PvAssumptions;
use pvinvestment\classes\Domain\RevenueSharingModel;
use pvinvestment\classes\Domain\SavingsPlanAssumptions;
use pvinvestment\classes\Domain\TaxAssumptions;

final class AuditCoverageTest extends TestCase
{
    public function testZeroPercentBatteryRevenueShareAllocatesAllRevenueToOperator(): void
    {
        $result = $this->annualCalculator()->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::profitSharing(
                annualRevenue: 1000.0,
                annualOperatingCosts: 100.0,
                sharingModel: RevenueSharingModel::profitSharing(
                    investorRevenueShare: 0.0,
                    operatorRevenueShare: 1.0,
                ),
            ),
            financingAssumptions: new FinancingAssumptions(),
            taxAssumptions: new TaxAssumptions(),
            savingsPlanAssumptions: new SavingsPlanAssumptions(),
        );

        self::assertSame(0.0, $result->investorBatteryRevenue);
        self::assertSame(1000.0, $result->operatorBatteryRevenue);
        self::assertSame(100.0, $result->investorBatteryCosts);
        self::assertSame(-100.0, $result->annualInvestorCashflow);
    }

    public function testHundredPercentBatteryRevenueShareAllocatesAllRevenueToInvestor(): void
    {
        $result = $this->annualCalculator()->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::profitSharing(
                annualRevenue: 1000.0,
                annualOperatingCosts: 100.0,
                sharingModel: RevenueSharingModel::profitSharing(
                    investorRevenueShare: 1.0,
                    operatorRevenueShare: 0.0,
                ),
            ),
            financingAssumptions: new FinancingAssumptions(),
            taxAssumptions: new TaxAssumptions(),
            savingsPlanAssumptions: new SavingsPlanAssumptions(),
        );

        self::assertSame(1000.0, $result->investorBatteryRevenue);
        self::assertSame(0.0, $result->operatorBatteryRevenue);
        self::assertSame(100.0, $result->investorBatteryCosts);
        self::assertSame(900.0, $result->annualInvestorCashflow);
    }

    public function testProfitSharingSixtyFiveThirtyFiveUsesGrossRevenueBasis(): void
    {
        $result = $this->annualCalculator()->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::profitSharing(
                annualRevenue: 1000.0,
                annualOperatingCosts: 200.0,
                sharingModel: RevenueSharingModel::profitSharing(
                    investorRevenueShare: 0.65,
                    operatorRevenueShare: 0.35,
                ),
            ),
            financingAssumptions: new FinancingAssumptions(),
            taxAssumptions: new TaxAssumptions(),
            savingsPlanAssumptions: new SavingsPlanAssumptions(),
        );

        self::assertSame(650.0, $result->investorBatteryRevenue);
        self::assertSame(350.0, $result->operatorBatteryRevenue);
        self::assertSame(200.0, $result->investorBatteryCosts);
        self::assertSame(450.0, $result->annualInvestorCashflow);
    }

    public function testFullyCapitalizableAncillaryCostsOnlyIncreaseDepreciationBasis(): void
    {
        $result = $this->taxCalculator()->calculate(
            taxAssumptions: new TaxAssumptions(
                incomeTaxRate: 0.30,
                acquisitionCost: 100000.0,
                capitalizableAncillaryCosts: 10000.0,
                immediatelyDeductibleCosts: 0.0,
                linearDepreciationRate: 0.10,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 20000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(110000.0, $result->capitalizableAcquisitionCosts);
        self::assertSame(0.0, $result->immediatelyDeductibleCosts);
        self::assertSame(110000.0, $result->depreciationBasis);
        self::assertSame(11000.0, $result->regularDepreciation);
        self::assertSame(9000.0, $result->taxableIncome);
    }

    public function testFullyImmediatelyDeductibleAncillaryCostsDoNotIncreaseDepreciationBasis(): void
    {
        $result = $this->taxCalculator()->calculate(
            taxAssumptions: new TaxAssumptions(
                incomeTaxRate: 0.30,
                acquisitionCost: 100000.0,
                capitalizableAncillaryCosts: 0.0,
                immediatelyDeductibleCosts: 10000.0,
                linearDepreciationRate: 0.10,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 20000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(100000.0, $result->capitalizableAcquisitionCosts);
        self::assertSame(10000.0, $result->immediatelyDeductibleCosts);
        self::assertSame(100000.0, $result->depreciationBasis);
        self::assertSame(10000.0, $result->regularDepreciation);
        self::assertSame(0.0, $result->taxableIncome);
    }

    public function testMixedAncillaryCostsAreSplitBetweenDepreciationBasisAndImmediateDeduction(): void
    {
        $result = $this->taxCalculator()->calculate(
            taxAssumptions: new TaxAssumptions(
                incomeTaxRate: 0.30,
                acquisitionCost: 100000.0,
                capitalizableAncillaryCosts: 6000.0,
                immediatelyDeductibleCosts: 4000.0,
                linearDepreciationRate: 0.10,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 20000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(106000.0, $result->capitalizableAcquisitionCosts);
        self::assertSame(4000.0, $result->immediatelyDeductibleCosts);
        self::assertSame(106000.0, $result->depreciationBasis);
        self::assertSame(10600.0, $result->regularDepreciation);
        self::assertSame(5400.0, $result->taxableIncome);
    }

    public function testIabOffAndSpecialDepreciationOffUsesOnlyRegularDepreciation(): void
    {
        $result = $this->taxCalculator()->calculate(
            taxAssumptions: new TaxAssumptions(
                incomeTaxRate: 0.40,
                acquisitionCost: 100000.0,
                linearDepreciationRate: 0.10,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 30000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(0.0, $result->iabDeduction);
        self::assertSame(0.0, $result->iabAddition);
        self::assertSame(0.0, $result->specialDepreciation);
        self::assertSame(10000.0, $result->regularDepreciation);
        self::assertSame(20000.0, $result->taxableIncome);
    }

    public function testIabOnAndSpecialDepreciationOffUsesReducedDepreciationBasis(): void
    {
        $result = $this->taxCalculator()->calculate(
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2026,
                incomeTaxRate: 0.40,
                acquisitionCost: 100000.0,
                linearDepreciationRate: 0.10,
                iabEnabled: true,
                iabAmount: 20000.0,
                iabDeductionYear: 2025,
                iabAdditionYear: 2026,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 30000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(0.0, $result->iabDeduction);
        self::assertSame(20000.0, $result->iabAddition);
        self::assertSame(20000.0, $result->iabAcquisitionCostReduction);
        self::assertSame(80000.0, $result->depreciationBasis);
        self::assertSame(8000.0, $result->regularDepreciation);
        self::assertSame(42000.0, $result->taxableIncome);
    }

    public function testIabOnAndSpecialDepreciationOnUsesSameReducedBasisForBothDepreciations(): void
    {
        $result = $this->taxCalculator()->calculate(
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2026,
                incomeTaxRate: 0.40,
                acquisitionCost: 100000.0,
                linearDepreciationRate: 0.10,
                iabEnabled: true,
                iabAmount: 20000.0,
                iabDeductionYear: 2025,
                iabAdditionYear: 2026,
                specialDepreciationEnabled: true,
                specialDepreciationRate: 0.20,
                specialDepreciationStartYear: 2026,
                specialDepreciationYears: 1,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 30000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(80000.0, $result->depreciationBasis);
        self::assertSame(8000.0, $result->regularDepreciation);
        self::assertSame(16000.0, $result->specialDepreciation);
        self::assertSame(26000.0, $result->taxableIncome);
    }

    public function testTaxRefundWithOneYearPaymentDelayDoesNotAffectCurrentCashflow(): void
    {
        $result = $this->annualCalculator()->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2026,
                incomeTaxRate: 0.40,
                immediatelyDeductibleCosts: 10000.0,
                taxPaymentDelayYears: 1,
            ),
            savingsPlanAssumptions: new SavingsPlanAssumptions(),
        );

        self::assertSame(-10000.0, $result->taxableIncome);
        self::assertSame(-4000.0, $result->taxAmount);
        self::assertSame(2027, $result->taxPaymentYear);
        self::assertSame(0.0, $result->annualTaxPayment);
        self::assertSame(0.0, $result->annualInvestorCashflow);
    }

    public function testSavingsPlanWithZeroStartingCapital(): void
    {
        $result = $this->annualCalculator()->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
            taxAssumptions: new TaxAssumptions(),
            savingsPlanAssumptions: new SavingsPlanAssumptions(startingCapital: 0.0),
        );

        self::assertSame(0.0, $result->savingsPlanStartingCapital);
        self::assertSame(0.0, $result->savingsPlanEndingCapital);
    }

    public function testSavingsPlanWithConfiguredPositiveStartingCapital(): void
    {
        $result = $this->annualCalculator()->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
            taxAssumptions: new TaxAssumptions(),
            savingsPlanAssumptions: new SavingsPlanAssumptions(startingCapital: 12345.67),
        );

        self::assertSame(12345.67, $result->savingsPlanStartingCapital);
        self::assertSame(12345.67, $result->savingsPlanEndingCapital);
    }

    public function testTaxCalculatorKeepsInterestConsistentWithCashflowTiming(): void
    {
        $timing = new ProjectTimingAssumptions(
            calculationYear: 2026,
            interestStartYear: 2026,
            interestStartMonth: 7,
        );

        $result = $this->annualCalculator()->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 12000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(annualInterest: 1200.0),
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2026,
                incomeTaxRate: 0.30,
            ),
            savingsPlanAssumptions: new SavingsPlanAssumptions(),
            timingAssumptions: $timing,
        );

        self::assertSame(600.0, $result->annualInterest);
        self::assertSame(600.0, $result->deductibleInterest);
        self::assertSame(11400.0, $result->taxableIncome);
    }

    private function annualCalculator(): AnnualInvestorCashflowCalculator
    {
        return new AnnualInvestorCashflowCalculator();
    }

    private function taxCalculator(): TaxCalculator
    {
        return new TaxCalculator();
    }
}

