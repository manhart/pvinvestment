<?php
declare(strict_types=1);

namespace pvinvestment\classes\Calculators;

use pvinvestment\classes\Domain\MoneyAmount;
use pvinvestment\classes\Domain\Scenario\ScenarioComparison;
use pvinvestment\classes\Domain\Scenario\ScenarioInput;
use pvinvestment\classes\Domain\Scenario\ScenarioResult;

final class ScenarioCalculator
{
    private readonly MonthlyScenarioCalculator $monthlyCalculator;
    private readonly YearlyAggregationCalculator $yearlyAggregationCalculator;

    public function __construct(
        ?MonthlyScenarioCalculator $monthlyCalculator = null,
        ?YearlyAggregationCalculator $yearlyAggregationCalculator = null,
    ) {
        $this->monthlyCalculator = $monthlyCalculator ?? new MonthlyScenarioCalculator();
        $this->yearlyAggregationCalculator = $yearlyAggregationCalculator ?? new YearlyAggregationCalculator();
    }

    public function calculate(ScenarioInput $scenario): ScenarioResult
    {
        $monthlyResults = $this->monthlyCalculator->calculate($scenario);
        $yearlyResults = $this->yearlyAggregationCalculator->aggregate($monthlyResults);
        $cumulativeInvestorCashflow = 0.0;
        $cumulativeTaxCashflow = 0.0;
        $cumulativeInvestorBatteryRevenue = 0.0;
        $breakEvenYear = null;

        foreach($yearlyResults as $yearResult) {
            $cumulativeInvestorCashflow += $yearResult->investorCashflowBeforeSavings;
            $cumulativeTaxCashflow += $yearResult->taxCashflow;
            $cumulativeInvestorBatteryRevenue += $yearResult->batteryInvestorRevenue;

            if($breakEvenYear === null && $cumulativeInvestorCashflow >= 0.0) {
                $breakEvenYear = $yearResult->year;
            }
        }

        $lastYear = $yearlyResults[count($yearlyResults) - 1] ?? null;
        $cumulativeInvestorCashflow = $this->money($cumulativeInvestorCashflow);
        $cumulativeTaxCashflow = $this->money($cumulativeTaxCashflow);
        $cumulativeInvestorBatteryRevenue = $this->money($cumulativeInvestorBatteryRevenue);
        $savingsPlanEndingCapital = $this->money($lastYear?->savingsEndValue ?? $scenario->savingsPlanAssumptions->startingCapital);
        $totalInvestorResult = $cumulativeInvestorCashflow + $savingsPlanEndingCapital;
        $totalInvestorResult = $this->money($totalInvestorResult);

        return new ScenarioResult(
            id: $scenario->id,
            description: $scenario->description,
            durationYears: $scenario->durationYears,
            annualResults: $yearlyResults,
            cumulativeInvestorCashflow: $cumulativeInvestorCashflow,
            cumulativeTaxCashflow: $cumulativeTaxCashflow,
            cumulativeInvestorBatteryRevenue: $cumulativeInvestorBatteryRevenue,
            savingsPlanEndingCapital: $savingsPlanEndingCapital,
            totalInvestorResult: $totalInvestorResult,
            breakEvenYear: $breakEvenYear,
            monthlyResults: $monthlyResults,
            yearlyResults: $yearlyResults,
        );
    }

    /**
     * @param list<ScenarioInput> $scenarios
     */
    public function compare(array $scenarios): ScenarioComparison
    {
        $results = [];
        foreach($scenarios as $scenario) {
            $results[] = $this->calculate($scenario);
        }

        return ScenarioComparison::fromResults($results);
    }

    private function money(float $amount): float
    {
        return MoneyAmount::fromEuro($amount)->toEuro();
    }
}
