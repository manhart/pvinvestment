<?php
declare(strict_types=1);

namespace pvinvestment\tests\Unit;

use PHPUnit\Framework\TestCase;
use pvinvestment\classes\Calculators\ScenarioCalculator;
use pvinvestment\classes\Calculators\TaxCalculator;
use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\FinancingAssumptions;
use pvinvestment\classes\Domain\PvAssumptions;
use pvinvestment\classes\Domain\SavingsPlanAssumptions;
use pvinvestment\classes\Domain\Scenario\ScenarioInput;
use pvinvestment\classes\Domain\Tax\TaxLossHandlingStrategy;
use pvinvestment\classes\Domain\Tax\TaxLossLedger;
use pvinvestment\classes\Domain\TaxAssumptions;

final class TaxLossHandlingTest extends TestCase
{
    public function testLossIsImmediatelyUsableByDefault(): void
    {
        $result = $this->taxCalculator()->calculate(
            taxAssumptions: $this->taxAssumptions(
                immediatelyDeductibleCosts: 10000.0,
                lossHandlingStrategy: TaxLossHandlingStrategy::IMMEDIATE,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(-10000.0, $result->taxableResultBeforeLoss);
        self::assertSame(10000.0, $result->lossUsed);
        self::assertSame(0.0, $result->lossCarriedForward);
        self::assertSame(-10000.0, $result->taxableResultAfterLoss);
        self::assertSame(-4000.0, $result->taxAmount);
    }

    public function testLossIsNotImmediatelyUsableWithCarryForwardStrategy(): void
    {
        $ledger = new TaxLossLedger();
        $result = $this->taxCalculator()->calculate(
            taxAssumptions: $this->taxAssumptions(
                immediatelyDeductibleCosts: 10000.0,
                lossHandlingStrategy: TaxLossHandlingStrategy::CARRY_FORWARD,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
            taxLossLedger: $ledger,
        );

        self::assertSame(0.0, $result->taxableResultAfterLoss);
        self::assertSame(10000.0, $result->lossCreated);
        self::assertSame(10000.0, $result->lossCarriedForward);
        self::assertSame(0.0, $result->taxAmount);
    }

    public function testLossCarryForwardOffsetsPositiveFollowingYear(): void
    {
        $ledger = new TaxLossLedger();
        $this->taxCalculator()->calculate(
            taxAssumptions: $this->taxAssumptions(
                calculationYear: 2026,
                immediatelyDeductibleCosts: 10000.0,
                lossHandlingStrategy: TaxLossHandlingStrategy::CARRY_FORWARD,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
            taxLossLedger: $ledger,
        );
        $followingYear = $this->taxCalculator()->calculate(
            taxAssumptions: $this->taxAssumptions(
                calculationYear: 2027,
                lossHandlingStrategy: TaxLossHandlingStrategy::CARRY_FORWARD,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 15000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
            taxLossLedger: $ledger,
        );

        self::assertSame(10000.0, $followingYear->lossUsed);
        self::assertSame(0.0, $followingYear->lossCarriedForward);
        self::assertSame(5000.0, $followingYear->taxableResultAfterLoss);
        self::assertSame(2000.0, $followingYear->taxAmount);
    }

    public function testLossCarryForwardCanSpanMultipleYears(): void
    {
        $ledger = new TaxLossLedger();
        $calculator = $this->taxCalculator();
        $calculator->calculate(
            taxAssumptions: $this->taxAssumptions(
                calculationYear: 2026,
                immediatelyDeductibleCosts: 30000.0,
                lossHandlingStrategy: TaxLossHandlingStrategy::CARRY_FORWARD,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
            taxLossLedger: $ledger,
        );
        $year2027 = $calculator->calculate(
            taxAssumptions: $this->taxAssumptions(calculationYear: 2027, lossHandlingStrategy: TaxLossHandlingStrategy::CARRY_FORWARD),
            pvAssumptions: new PvAssumptions(annualRevenue: 10000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
            taxLossLedger: $ledger,
        );
        $year2028 = $calculator->calculate(
            taxAssumptions: $this->taxAssumptions(calculationYear: 2028, lossHandlingStrategy: TaxLossHandlingStrategy::CARRY_FORWARD),
            pvAssumptions: new PvAssumptions(annualRevenue: 25000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
            taxLossLedger: $ledger,
        );

        self::assertSame(20000.0, $year2027->lossCarriedForward);
        self::assertSame(0.0, $year2027->taxAmount);
        self::assertSame(20000.0, $year2028->lossUsed);
        self::assertSame(5000.0, $year2028->taxableResultAfterLoss);
        self::assertSame(2000.0, $year2028->taxAmount);
    }

    public function testCarryBackOffsetsPriorYearProfit(): void
    {
        $ledger = new TaxLossLedger();
        $calculator = $this->taxCalculator();
        $calculator->calculate(
            taxAssumptions: $this->taxAssumptions(calculationYear: 2026, lossHandlingStrategy: TaxLossHandlingStrategy::CARRY_BACK),
            pvAssumptions: new PvAssumptions(annualRevenue: 20000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
            taxLossLedger: $ledger,
        );
        $lossYear = $calculator->calculate(
            taxAssumptions: $this->taxAssumptions(
                calculationYear: 2027,
                immediatelyDeductibleCosts: 10000.0,
                lossHandlingStrategy: TaxLossHandlingStrategy::CARRY_BACK,
                maxLossCarryBackAmount: 10000.0,
                maxLossCarryBackYears: 1,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
            taxLossLedger: $ledger,
        );

        self::assertSame(10000.0, $lossYear->lossUsed);
        self::assertSame(0.0, $lossYear->lossCreated);
        self::assertSame(-10000.0, $lossYear->taxableResultAfterLoss);
        self::assertSame(-4000.0, $lossYear->taxAmount);
    }

    public function testManualUsableLossOffsetsConfiguredAmount(): void
    {
        $result = $this->taxCalculator()->calculate(
            taxAssumptions: $this->taxAssumptions(
                calculationYear: 2026,
                lossHandlingStrategy: TaxLossHandlingStrategy::MANUAL,
                manualUsableLossByYear: [2026 => 3000.0],
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 10000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(3000.0, $result->lossUsed);
        self::assertSame(7000.0, $result->taxableResultAfterLoss);
        self::assertSame(2800.0, $result->taxAmount);
    }

    public function testNoneCreatesNoTaxRefund(): void
    {
        $result = $this->taxCalculator()->calculate(
            taxAssumptions: $this->taxAssumptions(
                immediatelyDeductibleCosts: 10000.0,
                lossHandlingStrategy: TaxLossHandlingStrategy::NONE,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(10000.0, $result->lossCreated);
        self::assertSame(0.0, $result->lossUsed);
        self::assertSame(0.0, $result->taxableResultAfterLoss);
        self::assertSame(0.0, $result->taxAmount);
    }

    public function testTaxPaymentDelayMonthsMoveCashflowMonth(): void
    {
        $result = $this->taxCalculator()->calculate(
            taxAssumptions: $this->taxAssumptions(
                immediatelyDeductibleCosts: 10000.0,
                taxPaymentDelayMonths: 1,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(-4000.0, $result->taxAmount);
        self::assertSame(2027, $result->taxCashflowYear);
        self::assertSame(1, $result->taxCashflowMonth);
        self::assertSame(0.0, $result->cashflowTaxPayment);
    }

    public function testTaxRateByYearOverridesDefaultTaxRate(): void
    {
        $result = $this->taxCalculator()->calculate(
            taxAssumptions: new TaxAssumptions(
                calculationYear: 2027,
                incomeTaxRate: 0.40,
                taxRateByYear: [2027 => 0.25],
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 10000.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(10000.0, $result->taxableResultAfterLoss);
        self::assertSame(2500.0, $result->taxAmount);
    }

    public function testScenarioComparisonDistinguishesImmediateAndCarryForward(): void
    {
        $comparison = (new ScenarioCalculator())->compare([
            $this->scenario('immediate', TaxLossHandlingStrategy::IMMEDIATE),
            $this->scenario('carry_forward', TaxLossHandlingStrategy::CARRY_FORWARD),
        ]);

        $immediate = $comparison->rowFor('immediate');
        $carryForward = $comparison->rowFor('carry_forward');

        self::assertSame(4000.0, $immediate['cumulativeInvestorCashflow']);
        self::assertSame(0.0, $carryForward['cumulativeInvestorCashflow']);
        self::assertSame(-4000.0, $carryForward['differenceToBase']['totalInvestorResult']);
    }

    private function scenario(string $id, string $lossHandlingStrategy): ScenarioInput
    {
        return new ScenarioInput(
            id: $id,
            description: $id,
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::none(),
            revenueSharingModel: BatteryModel::none()->sharingModel,
            financingAssumptions: new FinancingAssumptions(),
            taxAssumptions: $this->taxAssumptions(
                immediatelyDeductibleCosts: 10000.0,
                lossHandlingStrategy: $lossHandlingStrategy,
            ),
            savingsPlanAssumptions: new SavingsPlanAssumptions(),
            durationYears: 1,
        );
    }

    private function taxAssumptions(
        int $calculationYear = 2026,
        float $immediatelyDeductibleCosts = 0.0,
        string $lossHandlingStrategy = TaxLossHandlingStrategy::IMMEDIATE,
        float $maxLossCarryBackAmount = 0.0,
        int $maxLossCarryBackYears = 1,
        array $manualUsableLossByYear = [],
        int $taxPaymentDelayMonths = 0,
    ): TaxAssumptions {
        return new TaxAssumptions(
            calculationYear: $calculationYear,
            incomeTaxRate: 0.40,
            immediatelyDeductibleCosts: $immediatelyDeductibleCosts,
            lossHandlingStrategy: $lossHandlingStrategy,
            maxLossCarryBackAmount: $maxLossCarryBackAmount,
            maxLossCarryBackYears: $maxLossCarryBackYears,
            manualUsableLossByYear: $manualUsableLossByYear,
            taxPaymentDelayMonths: $taxPaymentDelayMonths,
        );
    }

    private function taxCalculator(): TaxCalculator
    {
        return new TaxCalculator();
    }
}
