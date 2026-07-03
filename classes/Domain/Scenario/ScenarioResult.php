<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain\Scenario;

use pvinvestment\classes\Domain\Results\MonthResult;
use pvinvestment\classes\Domain\Results\YearResult;

final class ScenarioResult
{
    /**
     * @param list<YearResult> $annualResults Kept as compatibility alias for aggregated yearly results.
     * @param list<MonthResult> $monthlyResults
     * @param list<YearResult>|null $yearlyResults
     */
    public readonly array $yearlyResults;

    public function __construct(
        public readonly string $id,
        public readonly string $description,
        public readonly int $durationYears,
        public readonly array $annualResults,
        public readonly float $cumulativeInvestorCashflow,
        public readonly float $cumulativeTaxCashflow,
        public readonly float $cumulativeInvestorBatteryRevenue,
        public readonly float $savingsPlanEndingCapital,
        public readonly float $totalInvestorResult,
        public readonly ?int $breakEvenYear,
        public readonly array $monthlyResults = [],
        ?array $yearlyResults = null,
    ) {
        $this->yearlyResults = $yearlyResults ?? $annualResults;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'durationYears' => $this->durationYears,
            'cumulativeInvestorCashflow' => $this->cumulativeInvestorCashflow,
            'cumulativeTaxCashflow' => $this->cumulativeTaxCashflow,
            'cumulativeInvestorBatteryRevenue' => $this->cumulativeInvestorBatteryRevenue,
            'savingsPlanEndingCapital' => $this->savingsPlanEndingCapital,
            'totalInvestorResult' => $this->totalInvestorResult,
            'breakEvenYear' => $this->breakEvenYear,
            'monthlyResults' => array_map(static fn(MonthResult $result): array => $result->toArray(), $this->monthlyResults),
            'yearlyResults' => array_map(static fn(YearResult $result): array => $result->toArray(), $this->yearlyResults),
            'annualResults' => array_map(static fn(YearResult $result): array => $result->toArray(), $this->annualResults),
        ];
    }
}
