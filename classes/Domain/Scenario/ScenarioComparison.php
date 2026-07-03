<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain\Scenario;

use InvalidArgumentException;

final class ScenarioComparison
{
    /**
     * @param list<ScenarioResult> $results
     * @param list<array<string, mixed>> $rows
     */
    private function __construct(
        public readonly ScenarioResult $baseScenario,
        public readonly array $results,
        public readonly array $rows,
    ) {}

    /**
     * @param list<ScenarioResult> $results
     */
    public static function fromResults(array $results): self
    {
        if(!$results) {
            throw new InvalidArgumentException('At least one scenario result is required.');
        }

        $baseScenario = $results[0];
        $rows = [];
        foreach($results as $result) {
            $rows[] = [
                'id' => $result->id,
                'description' => $result->description,
                'cumulativeInvestorCashflow' => $result->cumulativeInvestorCashflow,
                'cumulativeTaxCashflow' => $result->cumulativeTaxCashflow,
                'cumulativeInvestorBatteryRevenue' => $result->cumulativeInvestorBatteryRevenue,
                'savingsPlanEndingCapital' => $result->savingsPlanEndingCapital,
                'totalInvestorResult' => $result->totalInvestorResult,
                'breakEvenYear' => $result->breakEvenYear,
                'differenceToBase' => [
                    'cumulativeInvestorCashflow' => $result->cumulativeInvestorCashflow - $baseScenario->cumulativeInvestorCashflow,
                    'cumulativeTaxCashflow' => $result->cumulativeTaxCashflow - $baseScenario->cumulativeTaxCashflow,
                    'cumulativeInvestorBatteryRevenue' => $result->cumulativeInvestorBatteryRevenue - $baseScenario->cumulativeInvestorBatteryRevenue,
                    'savingsPlanEndingCapital' => $result->savingsPlanEndingCapital - $baseScenario->savingsPlanEndingCapital,
                    'totalInvestorResult' => $result->totalInvestorResult - $baseScenario->totalInvestorResult,
                ],
            ];
        }

        return new self($baseScenario, $results, $rows);
    }

    public function rowFor(string $scenarioId): array
    {
        foreach($this->rows as $row) {
            if($row['id'] === $scenarioId) {
                return $row;
            }
        }

        throw new InvalidArgumentException('Unknown scenario id: '.$scenarioId);
    }
}

