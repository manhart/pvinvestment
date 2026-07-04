<?php
declare(strict_types=1);

namespace pvinvestment\tests\Unit;

use PHPUnit\Framework\TestCase;
use pvinvestment\classes\Calculators\ScenarioCalculator;
use pvinvestment\classes\Demo\DemoScenarioFactory;
use pvinvestment\classes\Domain\RevenueSharingModel;
use pvinvestment\classes\Domain\Scenario\ScenarioInput;

final class DemoScenarioFactoryTest extends TestCase
{
    public function testDemoScenarioFactoryCreatesValidScenarios(): void
    {
        $scenarios = DemoScenarioFactory::comparisonScenarios();

        self::assertCount(3, $scenarios);
        foreach($scenarios as $scenario) {
            self::assertInstanceOf(ScenarioInput::class, $scenario);
            self::assertStringStartsWith('demo_', $scenario->id);
            self::assertSame(5, $scenario->durationYears);
        }

        self::assertSame(
            RevenueSharingModel::SHARING_BASE_GROSS_REVENUE,
            $scenarios[1]->batteryModel->sharingModel->sharingBase,
        );
        self::assertSame(
            RevenueSharingModel::SHARING_BASE_NET_REVENUE,
            $scenarios[2]->batteryModel->sharingModel->sharingBase,
        );
    }

    public function testScenarioCalculatorCanCalculateAllDemoScenarios(): void
    {
        $calculator = new ScenarioCalculator();

        foreach(DemoScenarioFactory::comparisonScenarios() as $scenario) {
            $result = $calculator->calculate($scenario);

            self::assertSame($scenario->id, $result->id);
            self::assertCount(60, $result->monthlyResults);
            self::assertCount(5, $result->yearlyResults);
            self::assertGreaterThan(0.0, $result->cumulativeInvestorBatteryRevenue);
        }
    }
}
