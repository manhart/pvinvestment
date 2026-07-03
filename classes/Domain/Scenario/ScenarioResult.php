<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain\Scenario;

use pvinvestment\classes\Domain\CalculationResult;

final class ScenarioResult
{
    /**
     * @param list<CalculationResult> $annualResults
     */
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
    ) {}

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
            'annualResults' => array_map(static fn(CalculationResult $result): array => $result->toArray(), $this->annualResults),
        ];
    }
}

