<?php
declare(strict_types=1);

namespace pvinvestment\tests\Unit;

use PHPUnit\Framework\TestCase;
use pvinvestment\classes\Calculators\MonthlyScenarioCalculator;
use pvinvestment\classes\Calculators\ScenarioCalculator;
use pvinvestment\classes\Calculators\YearlyAggregationCalculator;
use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\FinancingAssumptions;
use pvinvestment\classes\Domain\ProjectTimingAssumptions;
use pvinvestment\classes\Domain\PvAssumptions;
use pvinvestment\classes\Domain\SavingsPlanAssumptions;
use pvinvestment\classes\Domain\Scenario\ScenarioInput;
use pvinvestment\classes\Domain\TaxAssumptions;

final class MonthlyScenarioCalculatorTest extends TestCase
{
    public function testRevenueStartInNovemberCreatesOnlyNovemberAndDecemberRevenueInStartYear(): void
    {
        $months = $this->calculator()->calculate($this->scenario(
            pvAssumptions: new PvAssumptions(annualRevenue: 12000.0),
            batteryModel: BatteryModel::fullOwnership(annualRevenue: 2400.0),
            timingAssumptions: new ProjectTimingAssumptions(
                calculationYear: 2026,
                revenueStartYear: 2026,
                revenueStartMonth: 11,
            ),
        ));

        self::assertSame(0.0, $months[9]->pvRevenue);
        self::assertSame(1000.0, $months[10]->pvRevenue);
        self::assertSame(1000.0, $months[11]->pvRevenue);
        self::assertSame(200.0, $months[10]->batteryInvestorRevenue);
        self::assertSame(2000.0, $this->sum($months, 'pvRevenue'));
    }

    public function testDepreciationStartInSeptemberCreatesFourDepreciationMonthsInStartYear(): void
    {
        $months = $this->calculator()->calculate($this->scenario(
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2026,
                acquisitionCost: 120000.0,
                depreciationStartYear: 2026,
                depreciationStartMonth: 9,
                linearDepreciationRate: 0.10,
            ),
            timingAssumptions: new ProjectTimingAssumptions(
                calculationYear: 2026,
                depreciationStartYear: 2026,
                depreciationStartMonth: 9,
            ),
        ));

        self::assertSame(0.0, $months[7]->depreciation);
        self::assertSame(1000.0, $months[8]->depreciation);
        self::assertSame(1000.0, $months[11]->depreciation);
        self::assertSame(4000.0, $this->sum($months, 'depreciation'));
    }

    public function testInterestStartsBeforePrincipal(): void
    {
        $months = $this->calculator()->calculate($this->scenario(
            financingAssumptions: new FinancingAssumptions(annualInterest: 1200.0, annualRepayment: 2400.0),
            timingAssumptions: new ProjectTimingAssumptions(
                calculationYear: 2026,
                interestStartYear: 2026,
                interestStartMonth: 3,
                repaymentStartYear: 2026,
                repaymentStartMonth: 7,
            ),
        ));

        self::assertSame(0.0, $months[1]->financingInterest);
        self::assertSame(100.0, $months[2]->financingInterest);
        self::assertSame(0.0, $months[5]->financingPrincipal);
        self::assertSame(200.0, $months[6]->financingPrincipal);
        self::assertSame(1000.0, $this->sum($months, 'financingInterest'));
        self::assertSame(1200.0, $this->sum($months, 'financingPrincipal'));
    }

    public function testPrincipalCanStartLaterThanInterest(): void
    {
        $months = $this->calculator()->calculate($this->scenario(
            financingAssumptions: new FinancingAssumptions(annualInterest: 1200.0, annualRepayment: 2400.0),
            timingAssumptions: new ProjectTimingAssumptions(
                calculationYear: 2026,
                interestStartYear: 2026,
                interestStartMonth: 1,
                repaymentStartYear: 2026,
                repaymentStartMonth: 12,
            ),
        ));

        self::assertSame(100.0, $months[0]->financingInterest);
        self::assertSame(0.0, $months[10]->financingPrincipal);
        self::assertSame(200.0, $months[11]->financingPrincipal);
        self::assertSame(200.0, $this->sum($months, 'financingPrincipal'));
    }

    public function testTaxRefundWithPaymentDelayAppearsInFollowingYearDecember(): void
    {
        $months = $this->calculator()->calculate($this->scenario(
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2026,
                incomeTaxRate: 0.40,
                immediatelyDeductibleCosts: 12000.0,
                taxPaymentDelayYears: 1,
            ),
            durationYears: 2,
        ));

        self::assertSame(0.0, $months[11]->taxCashflow);
        self::assertSame(-4800.0, $months[23]->taxCashflow);
    }

    public function testMonthlySavingsContribution(): void
    {
        $months = $this->calculator()->calculate($this->scenario(
            savingsPlanAssumptions: new SavingsPlanAssumptions(monthlyContribution: 100.0),
            timingAssumptions: new ProjectTimingAssumptions(
                calculationYear: 2026,
                savingsPlanContributionStartYear: 2026,
                savingsPlanContributionStartMonth: 3,
            ),
        ));

        self::assertSame(0.0, $months[1]->savingsContribution);
        self::assertSame(100.0, $months[2]->savingsContribution);
        self::assertSame(1000.0, $months[11]->savingsEndValue);
    }

    public function testAnnualSavingsContribution(): void
    {
        $months = $this->calculator()->calculate($this->scenario(
            savingsPlanAssumptions: new SavingsPlanAssumptions(annualContribution: 1200.0),
            timingAssumptions: new ProjectTimingAssumptions(
                calculationYear: 2026,
                annualSavingsPlanContributionMonth: 6,
            ),
        ));

        self::assertSame(0.0, $months[4]->savingsContribution);
        self::assertSame(1200.0, $months[5]->savingsContribution);
        self::assertSame(1200.0, $months[11]->savingsEndValue);
    }

    public function testSavingsContributionFromPositiveFreeCashflow(): void
    {
        $months = $this->calculator()->calculate($this->scenario(
            pvAssumptions: new PvAssumptions(annualRevenue: 12000.0),
            savingsPlanAssumptions: new SavingsPlanAssumptions(positiveCashflowReinvestmentRate: 0.50),
        ));

        self::assertSame(500.0, $months[0]->savingsContribution);
        self::assertSame(500.0, $months[0]->freeCashflowAfterSavings);
        self::assertSame(6000.0, $months[11]->savingsEndValue);
    }

    public function testYearlyAggregationEqualsSumOfMonthlyValues(): void
    {
        $months = $this->calculator()->calculate($this->scenario(
            pvAssumptions: new PvAssumptions(annualRevenue: 12000.0, annualOperatingCosts: 1200.0),
            batteryModel: BatteryModel::fullOwnership(annualRevenue: 2400.0, annualOperatingCosts: 120.0),
            financingAssumptions: new FinancingAssumptions(annualInterest: 1200.0, annualRepayment: 2400.0),
            savingsPlanAssumptions: new SavingsPlanAssumptions(monthlyContribution: 100.0),
        ));
        $year = (new YearlyAggregationCalculator())->aggregate($months)[0];

        self::assertSame($this->sum($months, 'pvRevenue'), $year->pvRevenue);
        self::assertSame($this->sum($months, 'batteryInvestorRevenue'), $year->batteryInvestorRevenue);
        self::assertSame($this->sum($months, 'financingInterest'), $year->financingInterest);
        self::assertSame($this->sum($months, 'savingsContribution'), $year->savingsContribution);
        self::assertSame($months[11]->savingsEndValue, $year->savingsEndValue);
    }

    public function testScenarioCalculatorAnnualPathIsDocumentedAsTransitionWrapper(): void
    {
        $scenarioResult = (new ScenarioCalculator())->calculate($this->scenario(
            pvAssumptions: new PvAssumptions(annualRevenue: 12000.0),
        ));
        $documentation = file_get_contents(__DIR__.'/../../docs/model-spec/rechenlogik.md');

        self::assertSame(12000.0, $scenarioResult->cumulativeInvestorCashflow);
        self::assertIsString($documentation);
        self::assertStringContainsString('ScenarioCalculator bleibt vorerst ein Jahres-Wrapper', $documentation);
    }

    private function scenario(
        ?PvAssumptions $pvAssumptions = null,
        ?BatteryModel $batteryModel = null,
        ?FinancingAssumptions $financingAssumptions = null,
        ?TaxAssumptions $taxAssumptions = null,
        ?SavingsPlanAssumptions $savingsPlanAssumptions = null,
        ?ProjectTimingAssumptions $timingAssumptions = null,
        int $durationYears = 1,
    ): ScenarioInput {
        return new ScenarioInput(
            id: 'monthly',
            description: 'monthly',
            pvAssumptions: $pvAssumptions ?? new PvAssumptions(annualRevenue: 0.0),
            batteryModel: $batteryModel ?? BatteryModel::none(),
            revenueSharingModel: ($batteryModel ?? BatteryModel::none())->sharingModel,
            financingAssumptions: $financingAssumptions ?? new FinancingAssumptions(),
            taxAssumptions: $taxAssumptions ?? new TaxAssumptions(calculationYear: 2026),
            savingsPlanAssumptions: $savingsPlanAssumptions ?? new SavingsPlanAssumptions(),
            durationYears: $durationYears,
            timingAssumptions: $timingAssumptions,
        );
    }

    private function calculator(): MonthlyScenarioCalculator
    {
        return new MonthlyScenarioCalculator();
    }

    private function sum(array $months, string $property): float
    {
        $sum = 0.0;
        foreach($months as $month) {
            $sum += $month->{$property};
        }

        return $sum;
    }
}
