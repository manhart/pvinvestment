<?php
declare(strict_types=1);

namespace pvinvestment\tests\Unit;

use PHPUnit\Framework\TestCase;
use pvinvestment\classes\Calculators\AnnualInvestorCashflowCalculator;
use pvinvestment\classes\Calculators\TaxCalculator;
use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\FinancingAssumptions;
use pvinvestment\classes\Domain\PvAssumptions;
use pvinvestment\classes\Domain\RevenueSharingModel;
use pvinvestment\classes\Domain\SavingsPlanAssumptions;
use pvinvestment\classes\Domain\TaxAssumptions;

final class TaxCalculatorTest extends TestCase
{
    public function testTaxCalculationWithoutIabSeparatesCapitalizableAndImmediatelyDeductibleCosts(): void
    {
        $result = $this->taxCalculator()->calculate(
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2026,
                incomeTaxRate: 0.30,
                acquisitionCost: 100000.0,
                capitalizableAncillaryCosts: 10000.0,
                immediatelyDeductibleCosts: 5000.0,
                depreciationStartYear: 2026,
                depreciationStartMonth: 7,
                linearDepreciationRate: 0.06,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 30000.0, annualOperatingCosts: 4000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(annualInterest: 2000.0),
        );

        self::assertSame(110000.0, $result->capitalizableAcquisitionCosts);
        self::assertSame(5000.0, $result->immediatelyDeductibleCosts);
        self::assertSame(110000.0, $result->depreciationBasis);
        self::assertEqualsWithDelta(3300.0, $result->regularDepreciation, 0.000001);
        self::assertSame(2000.0, $result->deductibleInterest);
        self::assertEqualsWithDelta(15700.0, $result->taxableIncome, 0.000001);
        self::assertEqualsWithDelta(4710.0, $result->taxAmount, 0.000001);
    }

    public function testTaxCalculationWithIabDeductionAdditionAndAcquisitionCostReduction(): void
    {
        $deductionYear = $this->taxCalculator()->calculate(
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2025,
                incomeTaxRate: 0.40,
                acquisitionCost: 100000.0,
                capitalizableAncillaryCosts: 10000.0,
                depreciationStartYear: 2026,
                depreciationStartMonth: 7,
                linearDepreciationRate: 0.10,
                iabEnabled: true,
                iabAmount: 40000.0,
                iabDeductionYear: 2025,
                iabAdditionYear: 2026,
                taxPaymentDelayYears: 1,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(40000.0, $deductionYear->iabDeduction);
        self::assertSame(0.0, $deductionYear->iabAddition);
        self::assertSame(0.0, $deductionYear->iabAcquisitionCostReduction);
        self::assertSame(-40000.0, $deductionYear->taxableIncome);
        self::assertSame(-16000.0, $deductionYear->taxAmount);
        self::assertSame(2026, $deductionYear->taxPaymentYear);
        self::assertSame(0.0, $deductionYear->cashflowTaxPayment);

        $acquisitionYear = $this->taxCalculator()->calculate(
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2026,
                incomeTaxRate: 0.40,
                acquisitionCost: 100000.0,
                capitalizableAncillaryCosts: 10000.0,
                depreciationStartYear: 2026,
                depreciationStartMonth: 7,
                linearDepreciationRate: 0.10,
                iabEnabled: true,
                iabAmount: 40000.0,
                iabDeductionYear: 2025,
                iabAdditionYear: 2026,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(0.0, $acquisitionYear->iabDeduction);
        self::assertSame(40000.0, $acquisitionYear->iabAddition);
        self::assertSame(40000.0, $acquisitionYear->iabAcquisitionCostReduction);
        self::assertSame(70000.0, $acquisitionYear->depreciationBasis);
        self::assertEqualsWithDelta(3500.0, $acquisitionYear->regularDepreciation, 0.000001);
        self::assertEqualsWithDelta(36500.0, $acquisitionYear->taxableIncome, 0.000001);
        self::assertEqualsWithDelta(14600.0, $acquisitionYear->taxAmount, 0.000001);
    }

    public function testTaxCalculationWithParametrizedSpecialDepreciation(): void
    {
        $result = $this->taxCalculator()->calculate(
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2026,
                incomeTaxRate: 0.40,
                acquisitionCost: 100000.0,
                depreciationStartYear: 2026,
                depreciationStartMonth: 1,
                linearDepreciationRate: 0.05,
                specialDepreciationEnabled: true,
                specialDepreciationRate: 0.20,
                specialDepreciationStartYear: 2026,
                specialDepreciationYears: 4,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 50000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(5000.0, $result->regularDepreciation);
        self::assertSame(20000.0, $result->specialDepreciation);
        self::assertSame(25000.0, $result->taxableIncome);
        self::assertSame(10000.0, $result->taxAmount);
    }

    public function testTaxCalculationWithMonthlyDecliningDepreciation(): void
    {
        $result = $this->taxCalculator()->calculate(
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2026,
                incomeTaxRate: 0.40,
                acquisitionCost: 100000.0,
                depreciationStartYear: 2026,
                depreciationStartMonth: 4,
                depreciationMethod: TaxAssumptions::DEPRECIATION_DECLINING,
                decliningDepreciationRate: 0.125,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 50000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertEqualsWithDelta(9375.0, $result->regularDepreciation, 0.000001);
        self::assertEqualsWithDelta(40625.0, $result->taxableIncome, 0.000001);
        self::assertEqualsWithDelta(16250.0, $result->taxAmount, 0.000001);
    }

    public function testProfitSharingBatteryUsesInvestorShareForTaxAndCashflow(): void
    {
        $result = (new AnnualInvestorCashflowCalculator())->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 10000.0, annualOperatingCosts: 1000.0),
            batteryModel: BatteryModel::profitSharing(
                annualRevenue: 10000.0,
                annualOperatingCosts: 2000.0,
                sharingModel: RevenueSharingModel::profitSharing(
                    investorRevenueShare: 0.30,
                    operatorRevenueShare: 0.70,
                ),
            ),
            financingAssumptions: new FinancingAssumptions(annualInterest: 500.0, annualRepayment: 1000.0),
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2026,
                incomeTaxRate: 0.20,
            ),
            savingsPlanAssumptions: new SavingsPlanAssumptions(),
        );

        self::assertSame(3000.0, $result->investorBatteryRevenue);
        self::assertSame(7000.0, $result->operatorBatteryRevenue);
        self::assertSame(9500.0, $result->taxableIncome);
        self::assertSame(1900.0, $result->annualTaxPayment);
        self::assertSame(6600.0, $result->annualInvestorCashflow);
    }

    private function taxCalculator(): TaxCalculator
    {
        return new TaxCalculator();
    }
}

