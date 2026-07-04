<?php
declare(strict_types=1);

namespace pvinvestment\tests\Unit;

use PHPUnit\Framework\TestCase;
use pvinvestment\classes\Calculators\ScenarioCalculator;
use pvinvestment\classes\Demo\DemoScenarioFactory;
use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\RevenueSharingModel;
use pvinvestment\classes\Domain\Scenario\ScenarioInput;
use pvinvestment\classes\Form\ScenarioFormData;
use pvinvestment\classes\Form\ScenarioFormMapper;
use pvinvestment\classes\Form\ScenarioFormValidator;

final class ScenarioFormTest extends TestCase
{
    public function testDefaultFormValuesMapToValidScenarioInput(): void
    {
        $scenario = $this->map([]);
        $result = (new ScenarioCalculator())->calculate($scenario);

        self::assertInstanceOf(ScenarioInput::class, $scenario);
        self::assertSame('Demo Batterie Vollerwerb', $scenario->description);
        self::assertCount(60, $result->monthlyResults);
        self::assertGreaterThan(0.0, $result->cumulativeInvestorBatteryRevenue);
    }

    public function testBrokerageDefaultsToCapitalizableAndIabEligible(): void
    {
        $scenario = $this->map([]);

        self::assertSame(9000.0, $scenario->taxAssumptions->capitalizableAncillaryCosts);
        self::assertSame(0.0, $scenario->taxAssumptions->immediatelyDeductibleCosts);
        self::assertSame(9000.0, $scenario->taxAssumptions->iabEligibleCapitalizableAncillaryCosts);
        self::assertSame(229000.0, $scenario->taxAssumptions->iabEligibleInvestmentBasis());
    }

    public function testCapitalizedBrokerageCanBeMarkedAsNotIabEligible(): void
    {
        $scenario = $this->map([
            'brokerageTreatment' => 'capitalize',
            'brokerageFee' => '8000',
            'brokerageIabEligible' => '0',
        ]);

        self::assertSame(8000.0, $scenario->taxAssumptions->capitalizableAncillaryCosts);
        self::assertSame(0.0, $scenario->taxAssumptions->immediatelyDeductibleCosts);
        self::assertSame(0.0, $scenario->taxAssumptions->iabEligibleCapitalizableAncillaryCosts);
        self::assertSame(220000.0, $scenario->taxAssumptions->iabEligibleInvestmentBasis());
    }

    public function testImmediatelyDeductibleBrokerageIsNotInIabBasis(): void
    {
        $scenario = $this->map([
            'brokerageTreatment' => 'immediate',
            'brokerageFee' => '8000',
        ]);

        self::assertSame(0.0, $scenario->taxAssumptions->capitalizableAncillaryCosts);
        self::assertSame(8000.0, $scenario->taxAssumptions->immediatelyDeductibleCosts);
        self::assertSame(0.0, $scenario->taxAssumptions->iabEligibleCapitalizableAncillaryCosts);
        self::assertSame(220000.0, $scenario->taxAssumptions->iabEligibleInvestmentBasis());
    }

    public function testIgnoredBrokerageIsNeitherCapitalizedNorImmediatelyDeducted(): void
    {
        $scenario = $this->map([
            'brokerageTreatment' => 'ignore',
            'brokerageFee' => '8000',
        ]);

        self::assertSame(0.0, $scenario->taxAssumptions->capitalizableAncillaryCosts);
        self::assertSame(0.0, $scenario->taxAssumptions->immediatelyDeductibleCosts);
        self::assertSame(0.0, $scenario->taxAssumptions->iabEligibleCapitalizableAncillaryCosts);
        self::assertSame(220000.0, $scenario->taxAssumptions->iabEligibleInvestmentBasis());
    }

    public function testProfitSharingGrossRevenueFromFormValues(): void
    {
        $scenario = $this->map([
            'batteryModel' => BatteryModel::MODEL_PROFIT_SHARING,
            'sharingBase' => RevenueSharingModel::SHARING_BASE_GROSS_REVENUE,
            'investorRevenueSharePercent' => '65',
            'investorCostSharePercent' => '65',
            'investorCapexSharePercent' => '65',
        ]);

        self::assertSame(BatteryModel::MODEL_PROFIT_SHARING, $scenario->batteryModel->model);
        self::assertSame(RevenueSharingModel::SHARING_BASE_GROSS_REVENUE, $scenario->batteryModel->sharingModel->sharingBase);
        self::assertSame(0.65, $scenario->batteryModel->sharingModel->investorRevenueShare);
        self::assertSame(0.35, $scenario->batteryModel->sharingModel->operatorRevenueShare);
        self::assertSame(0.65, $scenario->batteryModel->sharingModel->investorCostShare);
    }

    public function testProfitSharingNetRevenueFromFormValues(): void
    {
        $scenario = $this->map([
            'batteryModel' => BatteryModel::MODEL_PROFIT_SHARING,
            'sharingBase' => RevenueSharingModel::SHARING_BASE_NET_REVENUE,
            'investorRevenueSharePercent' => '65',
            'investorCostSharePercent' => '65',
            'investorCapexSharePercent' => '65',
        ]);

        self::assertSame(RevenueSharingModel::SHARING_BASE_NET_REVENUE, $scenario->batteryModel->sharingModel->sharingBase);
        self::assertSame(0.65, $scenario->revenueSharingModel->investorRevenueShare);
        self::assertSame(0.35, $scenario->revenueSharingModel->operatorRevenueShare);
    }

    public function testBatteryCapexShareIsTakenFromForm(): void
    {
        $scenario = $this->map([
            'batteryModel' => BatteryModel::MODEL_PROFIT_SHARING,
            'investorCapexSharePercent' => '65',
            'batteryCapex' => '30000',
        ]);

        self::assertSame(0.65, $scenario->batteryModel->sharingModel->investorCapexShare);
        self::assertSame(19500.0, $scenario->batteryModel->annualAllocation()['investorCapex']);
    }

    public function testSavingsStartingCapitalIsTakenFromForm(): void
    {
        $scenario = $this->map([
            'savingsStartingCapital' => '12345.67',
        ]);

        self::assertSame(12345.67, $scenario->savingsPlanAssumptions->startingCapital);
    }

    public function testInvalidPercentCreatesValidationError(): void
    {
        $errors = $this->validate([
            'investorRevenueSharePercent' => '150',
        ]);

        self::assertArrayHasKey('investorRevenueSharePercent', $errors);
    }

    public function testInvalidMonthCreatesValidationError(): void
    {
        $errors = $this->validate([
            'revenueStartMonth' => '13',
        ]);

        self::assertArrayHasKey('revenueStartMonth', $errors);
    }

    /**
     * @param array<string, string> $overrides
     */
    private function map(array $overrides): ScenarioInput
    {
        $data = ScenarioFormData::fromPost(array_merge(DemoScenarioFactory::defaultFormValues(), $overrides));
        $errors = (new ScenarioFormValidator())->validate($data);

        self::assertSame([], $errors);

        return (new ScenarioFormMapper())->map($data);
    }

    /**
     * @param array<string, string> $overrides
     * @return array<string, string>
     */
    private function validate(array $overrides): array
    {
        $data = ScenarioFormData::fromPost(array_merge(DemoScenarioFactory::defaultFormValues(), $overrides));

        return (new ScenarioFormValidator())->validate($data);
    }
}
