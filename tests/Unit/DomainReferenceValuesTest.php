<?php
declare(strict_types=1);

namespace pvinvestment\tests\Unit;

use PHPUnit\Framework\TestCase;
use pvinvestment\classes\Calculators\ScenarioCalculator;
use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\FinancingAssumptions;
use pvinvestment\classes\Domain\ProjectTimingAssumptions;
use pvinvestment\classes\Domain\PvAssumptions;
use pvinvestment\classes\Domain\RevenueSharingModel;
use pvinvestment\classes\Domain\SavingsPlanAssumptions;
use pvinvestment\classes\Domain\Scenario\ScenarioInput;
use pvinvestment\classes\Domain\Tax\TaxLossHandlingStrategy;
use pvinvestment\classes\Domain\TaxAssumptions;

final class DomainReferenceValuesTest extends TestCase
{
    public function testScenarioFixturesAreValidJson(): void
    {
        foreach([
            'tests/fixtures/excel_reference_values.json',
            'tests/fixtures/scenarios/pv_battery_mid_case.json',
            'tests/fixtures/scenarios/battery_full_ownership.json',
            'tests/fixtures/scenarios/battery_profit_sharing_65_35.json',
            'tests/fixtures/domain_expected_values.json',
        ] as $path) {
            $data = $this->readJson($path);
            self::assertIsArray($data, $path);
        }
    }

    public function testDomainReferenceValuesAreReproducible(): void
    {
        $fixture = $this->readJson('tests/fixtures/domain_expected_values.json');
        $calculator = new ScenarioCalculator();

        foreach($fixture['scenarios'] as $scenarioId => $definition) {
            $result = $calculator->calculate($this->scenarioFromDefinition($scenarioId, $definition));
            $expected = $definition['domainExpectedValues'];

            self::assertEqualsWithDelta($expected['cumulativeInvestorCashflow'], $result->cumulativeInvestorCashflow, 0.01, $scenarioId);
            self::assertEqualsWithDelta($expected['cumulativeTaxCashflow'], $result->cumulativeTaxCashflow, 0.01, $scenarioId);
            self::assertEqualsWithDelta($expected['cumulativeInvestorBatteryRevenue'], $result->cumulativeInvestorBatteryRevenue, 0.01, $scenarioId);
            self::assertEqualsWithDelta($expected['savingsPlanEndingCapital'], $result->savingsPlanEndingCapital, 0.01, $scenarioId);
            self::assertEqualsWithDelta($expected['totalInvestorResult'], $result->totalInvestorResult, 0.01, $scenarioId);
            self::assertSame($expected['breakEvenYear'], $result->breakEvenYear, $scenarioId);
        }
    }

    public function testExcelDeviationsAreDocumentedWhenDomainValuesDiffer(): void
    {
        $fixture = $this->readJson('tests/fixtures/domain_expected_values.json');
        $documentation = file_get_contents(__DIR__.'/../../docs/model-spec/domain-referenzwerte.md');

        self::assertIsString($documentation);
        foreach($fixture['scenarios'] as $scenarioId => $definition) {
            self::assertStringContainsString($scenarioId, $documentation);
            foreach($definition['deviations'] as $deviation) {
                if(abs($deviation['absoluteDeviation']) > 0.01) {
                    self::assertNotEmpty($deviation['reasonCodes'], $scenarioId);
                    foreach($deviation['reasonCodes'] as $reasonCode) {
                        self::assertStringContainsString($reasonCode, $documentation);
                    }
                }
            }
        }
    }

    public function testScenarioCalculatorCalculatesExpectedDomainValuesWithinCentTolerance(): void
    {
        $definition = $this->readJson('tests/fixtures/domain_expected_values.json')['scenarios']['battery_profit_sharing_65_35'];
        $result = (new ScenarioCalculator())->calculate($this->scenarioFromDefinition('battery_profit_sharing_65_35', $definition));

        self::assertEqualsWithDelta(13965.83, $result->totalInvestorResult, 0.01);
        self::assertEqualsWithDelta(30550.0, $result->cumulativeInvestorBatteryRevenue, 0.01);
    }

    private function scenarioFromDefinition(string $scenarioId, array $definition): ScenarioInput
    {
        $inputs = $definition['inputs'];
        $batteryModel = $this->batteryModel($inputs);

        return new ScenarioInput(
            id: $scenarioId,
            description: $definition['title'],
            pvAssumptions: new PvAssumptions(
                annualRevenue: (float)$inputs['pvAnnualRevenue'],
                annualOperatingCosts: (float)$inputs['pvAnnualOperatingCosts'],
            ),
            batteryModel: $batteryModel,
            revenueSharingModel: $batteryModel->sharingModel,
            financingAssumptions: new FinancingAssumptions(
                annualInterest: (float)$inputs['financingAnnualInterest'],
                annualRepayment: (float)$inputs['financingAnnualRepayment'],
            ),
            taxAssumptions: $this->taxAssumptions($inputs['tax']),
            savingsPlanAssumptions: new SavingsPlanAssumptions(
                startingCapital: (float)$inputs['savingsStartingCapital'],
            ),
            durationYears: (int)$definition['durationYears'],
            timingAssumptions: $this->timingAssumptions($inputs['timing']),
        );
    }

    private function batteryModel(array $inputs): BatteryModel
    {
        if($inputs['batteryModel'] === BatteryModel::MODEL_FULL_OWNERSHIP) {
            return BatteryModel::fullOwnership(
                annualRevenue: (float)$inputs['batteryAnnualRevenue'],
                annualOperatingCosts: (float)$inputs['batteryAnnualOperatingCosts'],
            );
        }

        if($inputs['batteryModel'] === BatteryModel::MODEL_PROFIT_SHARING) {
            return BatteryModel::profitSharing(
                annualRevenue: (float)$inputs['batteryAnnualRevenue'],
                annualOperatingCosts: (float)$inputs['batteryAnnualOperatingCosts'],
                sharingModel: RevenueSharingModel::profitSharing(
                    investorRevenueShare: (float)$inputs['investorBatteryRevenueShare'],
                    operatorRevenueShare: (float)$inputs['operatorBatteryRevenueShare'],
                    sharingBase: $inputs['sharingBase'],
                    investorCostShare: (float)$inputs['investorBatteryCostShare'],
                    operatorCostShare: (float)$inputs['operatorBatteryCostShare'],
                ),
            );
        }

        return BatteryModel::none();
    }

    private function taxAssumptions(array $tax): TaxAssumptions
    {
        return new TaxAssumptions(
            calculationYear: (int)$tax['calculationYear'],
            incomeTaxRate: (float)($tax['incomeTaxRate'] ?? 0.0),
            acquisitionCost: (float)($tax['acquisitionCost'] ?? 0.0),
            capitalizableAncillaryCosts: (float)($tax['capitalizableAncillaryCosts'] ?? 0.0),
            immediatelyDeductibleCosts: (float)($tax['immediatelyDeductibleCosts'] ?? 0.0),
            depreciationStartYear: (int)($tax['depreciationStartYear'] ?? $tax['calculationYear']),
            depreciationStartMonth: (int)($tax['depreciationStartMonth'] ?? 1),
            linearDepreciationRate: (float)($tax['linearDepreciationRate'] ?? 0.0),
            lossHandlingStrategy: $tax['lossHandlingStrategy'] ?? TaxLossHandlingStrategy::IMMEDIATE,
        );
    }

    private function timingAssumptions(array $timing): ProjectTimingAssumptions
    {
        return new ProjectTimingAssumptions(
            calculationYear: (int)$timing['calculationYear'],
            investmentYear: (int)($timing['investmentYear'] ?? $timing['calculationYear']),
            investmentMonth: (int)($timing['investmentMonth'] ?? 1),
            depreciationStartYear: (int)($timing['depreciationStartYear'] ?? $timing['calculationYear']),
            depreciationStartMonth: (int)($timing['depreciationStartMonth'] ?? 1),
            eegCommissioningYear: (int)($timing['eegCommissioningYear'] ?? $timing['calculationYear']),
            eegCommissioningMonth: (int)($timing['eegCommissioningMonth'] ?? 1),
            gridConnectionYear: (int)($timing['gridConnectionYear'] ?? $timing['calculationYear']),
            gridConnectionMonth: (int)($timing['gridConnectionMonth'] ?? 1),
            revenueStartYear: (int)($timing['revenueStartYear'] ?? $timing['calculationYear']),
            revenueStartMonth: (int)($timing['revenueStartMonth'] ?? 1),
            interestStartYear: (int)($timing['interestStartYear'] ?? $timing['calculationYear']),
            interestStartMonth: (int)($timing['interestStartMonth'] ?? 1),
            repaymentStartYear: (int)($timing['repaymentStartYear'] ?? $timing['calculationYear']),
            repaymentStartMonth: (int)($timing['repaymentStartMonth'] ?? 1),
        );
    }

    private function readJson(string $path): array
    {
        $contents = file_get_contents(__DIR__.'/../../'.$path);
        self::assertIsString($contents, $path);
        $data = json_decode($contents, true);
        self::assertSame(JSON_ERROR_NONE, json_last_error(), $path.': '.json_last_error_msg());
        self::assertIsArray($data, $path);

        return $data;
    }
}
