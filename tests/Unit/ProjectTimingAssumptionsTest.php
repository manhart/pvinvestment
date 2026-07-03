<?php
declare(strict_types=1);

namespace pvinvestment\tests\Unit;

use PHPUnit\Framework\TestCase;
use pvinvestment\classes\Calculators\AnnualInvestorCashflowCalculator;
use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\FinancingAssumptions;
use pvinvestment\classes\Domain\ProjectTimingAssumptions;
use pvinvestment\classes\Domain\PvAssumptions;
use pvinvestment\classes\Domain\SavingsPlanAssumptions;
use pvinvestment\classes\Domain\TaxAssumptions;

final class ProjectTimingAssumptionsTest extends TestCase
{
    public function testAnnualCalculatorKeepsCoreProjectDatesSeparate(): void
    {
        $timing = new ProjectTimingAssumptions(
            calculationYear: 2026,
            investmentYear: 2025,
            investmentMonth: 11,
            depreciationStartYear: 2026,
            depreciationStartMonth: 7,
            eegCommissioningYear: 2026,
            eegCommissioningMonth: 2,
            gridConnectionYear: 2026,
            gridConnectionMonth: 5,
            revenueStartYear: 2026,
            revenueStartMonth: 6,
            interestStartYear: 2026,
            interestStartMonth: 3,
            repaymentStartYear: 2026,
            repaymentStartMonth: 9,
            taxPaymentYear: 2027,
            savingsPlanContributionStartYear: 2026,
            savingsPlanContributionStartMonth: 4,
            annualSavingsPlanContributionMonth: 3,
        );

        $result = (new AnnualInvestorCashflowCalculator())->calculate(
            pvAssumptions: new PvAssumptions(annualRevenue: 12000.0, annualOperatingCosts: 1200.0),
            batteryModel: BatteryModel::fullOwnership(annualRevenue: 2400.0, annualOperatingCosts: 120.0),
            financingAssumptions: new FinancingAssumptions(annualInterest: 1200.0, annualRepayment: 2400.0),
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2026,
                incomeTaxRate: 0.25,
                acquisitionCost: 120000.0,
                depreciationStartYear: 2026,
                depreciationStartMonth: 1,
                linearDepreciationRate: 0.10,
            ),
            savingsPlanAssumptions: new SavingsPlanAssumptions(
                startingCapital: 1000.0,
                monthlyContribution: 100.0,
                annualContribution: 500.0,
            ),
            timingAssumptions: $timing,
        );

        self::assertSame(2025, $result->investmentYear);
        self::assertSame(11, $result->investmentMonth);
        self::assertSame(2026, $result->depreciationStartYear);
        self::assertSame(7, $result->depreciationStartMonth);
        self::assertSame(2026, $result->eegCommissioningYear);
        self::assertSame(2, $result->eegCommissioningMonth);
        self::assertSame(2026, $result->gridConnectionYear);
        self::assertSame(5, $result->gridConnectionMonth);
        self::assertSame(2026, $result->revenueStartYear);
        self::assertSame(6, $result->revenueStartMonth);
        self::assertSame(2026, $result->interestStartYear);
        self::assertSame(3, $result->interestStartMonth);
        self::assertSame(2026, $result->repaymentStartYear);
        self::assertSame(9, $result->repaymentStartMonth);
        self::assertSame(2027, $result->taxPaymentYear);
        self::assertSame(2026, $result->savingsPlanContributionStartYear);
        self::assertSame(4, $result->savingsPlanContributionStartMonth);

        self::assertEqualsWithDelta(7 / 12, $result->revenueMonthFactor, 0.000001);
        self::assertEqualsWithDelta(10 / 12, $result->interestMonthFactor, 0.000001);
        self::assertEqualsWithDelta(4 / 12, $result->repaymentMonthFactor, 0.000001);
        self::assertEqualsWithDelta(9 / 12, $result->savingsPlanContributionMonthFactor, 0.000001);

        self::assertEqualsWithDelta(7000.0, $result->pvRevenue, 0.000001);
        self::assertEqualsWithDelta(700.0, $result->pvOperatingCosts, 0.000001);
        self::assertEqualsWithDelta(1400.0, $result->investorBatteryRevenue, 0.000001);
        self::assertEqualsWithDelta(70.0, $result->investorBatteryCosts, 0.000001);
        self::assertEqualsWithDelta(1000.0, $result->annualInterest, 0.000001);
        self::assertEqualsWithDelta(800.0, $result->annualRepayment, 0.000001);
        self::assertEqualsWithDelta(6000.0, $result->regularDepreciation, 0.000001);
        self::assertEqualsWithDelta(157.5, $result->taxAmount, 0.000001);
        self::assertSame(0.0, $result->annualTaxPayment);
        self::assertEqualsWithDelta(5830.0, $result->annualInvestorCashflow, 0.000001);
        self::assertEqualsWithDelta(900.0, $result->savingsPlanFixedContribution, 0.000001);
        self::assertEqualsWithDelta(1900.0, $result->savingsPlanEndingCapital, 0.000001);
    }
}
