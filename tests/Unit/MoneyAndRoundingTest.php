<?php
declare(strict_types=1);

namespace pvinvestment\tests\Unit;

use PHPUnit\Framework\TestCase;
use pvinvestment\classes\Calculators\TaxCalculator;
use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\FinancingAssumptions;
use pvinvestment\classes\Domain\MoneyAmount;
use pvinvestment\classes\Domain\PercentageRate;
use pvinvestment\classes\Domain\PvAssumptions;
use pvinvestment\classes\Domain\TaxAssumptions;

final class MoneyAndRoundingTest extends TestCase
{
    public function testCentExactAdditionAndSubtraction(): void
    {
        $amount = MoneyAmount::fromCents(100)
            ->add(MoneyAmount::fromCents(25))
            ->subtract(MoneyAmount::fromCents(30));

        self::assertSame(95, $amount->toCents());
        self::assertSame(0.95, $amount->toEuro());
    }

    public function testPercentageCalculationOnMoneyAmounts(): void
    {
        $money = MoneyAmount::fromEuro(1000.00);

        self::assertSame(35000, $money->multiplyRate(PercentageRate::fromDecimal(0.35))->toCents());
        self::assertSame(35000, $money->multiplyRate(PercentageRate::fromPercent(35.0))->toCents());
        self::assertSame(35000, $money->multiplyRate(PercentageRate::fromBasisPoints(3500))->toCents());
    }

    public function testPositiveTaxAmountIsRoundedToCents(): void
    {
        $result = (new TaxCalculator())->calculate(
            taxAssumptions: new TaxAssumptions(
                incomeTaxRate: 0.20,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 1000.025),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(200.01, $result->taxAmount);
        self::assertSame(200.01, $result->cashflowTaxPayment);
    }

    public function testNegativeTaxRefundIsRoundedToCents(): void
    {
        $result = (new TaxCalculator())->calculate(
            taxAssumptions: new TaxAssumptions(
                incomeTaxRate: 0.20,
                immediatelyDeductibleCosts: 1000.025,
            ),
            pvAssumptions: new PvAssumptions(annualRevenue: 0.0),
            batteryModel: BatteryModel::none(),
            financingAssumptions: new FinancingAssumptions(),
        );

        self::assertSame(-200.01, $result->taxAmount);
        self::assertSame(-200.01, $result->cashflowTaxPayment);
    }

    public function testCumulativeAnnualValuesDoNotDriftWhenUsingCentIntegers(): void
    {
        $sum = MoneyAmount::fromCents(0);
        for($i = 0; $i < 1200; $i++) {
            $sum = $sum->add(MoneyAmount::fromEuro(0.10));
        }

        self::assertSame(12000, $sum->toCents());
        self::assertSame(120.0, $sum->toEuro());
    }
}

