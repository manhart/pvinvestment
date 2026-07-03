<?php
declare(strict_types=1);

namespace pvinvestment\classes\Calculators;

use InvalidArgumentException;
use pvinvestment\classes\Domain\Results\MonthResult;
use pvinvestment\classes\Domain\Results\YearResult;

final class YearlyAggregationCalculator
{
    /**
     * @param list<MonthResult> $months
     * @return list<YearResult>
     */
    public function aggregate(array $months): array
    {
        $byYear = [];
        foreach($months as $month) {
            if(!$month instanceof MonthResult) {
                throw new InvalidArgumentException('Monthly aggregation requires MonthResult instances.');
            }
            $byYear[$month->year][] = $month;
        }

        ksort($byYear);
        $years = [];
        foreach($byYear as $year => $yearMonths) {
            $years[] = $this->aggregateYear((int)$year, $yearMonths);
        }

        return $years;
    }

    /**
     * @param list<MonthResult> $months
     */
    public function aggregateYear(int $year, array $months): YearResult
    {
        if(!$months) {
            throw new InvalidArgumentException('At least one month is required for yearly aggregation.');
        }

        $lastMonth = $months[count($months) - 1];

        return new YearResult(
            year: $year,
            months: $months,
            pvRevenue: $this->sum($months, static fn(MonthResult $month): float => $month->pvRevenue),
            batteryGrossRevenue: $this->sum($months, static fn(MonthResult $month): float => $month->batteryGrossRevenue),
            batteryInvestorRevenue: $this->sum($months, static fn(MonthResult $month): float => $month->batteryInvestorRevenue),
            batteryInvestorCosts: $this->sum($months, static fn(MonthResult $month): float => $month->batteryInvestorCosts),
            operatingCosts: $this->sum($months, static fn(MonthResult $month): float => $month->operatingCosts),
            financingInterest: $this->sum($months, static fn(MonthResult $month): float => $month->financingInterest),
            financingPrincipal: $this->sum($months, static fn(MonthResult $month): float => $month->financingPrincipal),
            depreciation: $this->sum($months, static fn(MonthResult $month): float => $month->depreciation),
            taxableResultComponent: $this->sum($months, static fn(MonthResult $month): float => $month->taxableResultComponent),
            taxCashflow: $this->sum($months, static fn(MonthResult $month): float => $month->taxCashflow),
            investorCashflowBeforeSavings: $this->sum($months, static fn(MonthResult $month): float => $month->investorCashflowBeforeSavings),
            savingsContribution: $this->sum($months, static fn(MonthResult $month): float => $month->savingsContribution),
            savingsReturn: $this->sum($months, static fn(MonthResult $month): float => $month->savingsReturn),
            savingsTax: $this->sum($months, static fn(MonthResult $month): float => $month->savingsTax),
            savingsEndValue: $lastMonth->savingsEndValue,
            freeCashflowAfterSavings: $this->sum($months, static fn(MonthResult $month): float => $month->freeCashflowAfterSavings),
        );
    }

    /**
     * @param list<MonthResult> $months
     */
    private function sum(array $months, callable $accessor): float
    {
        $sum = 0.0;
        foreach($months as $month) {
            $sum += $accessor($month);
        }

        return $sum;
    }
}
