<?php
declare(strict_types=1);

namespace pvinvestment\guis\GUI_PvInvestment;

use pool\classes\GUI\GUI_Module;
use pvinvestment\classes\Calculators\ScenarioCalculator;
use pvinvestment\classes\Demo\DemoScenarioFactory;
use pvinvestment\classes\Domain\Results\YearResult;
use pvinvestment\classes\Domain\Scenario\ScenarioInput;
use pvinvestment\classes\Domain\Scenario\ScenarioResult;

final class GUI_PvInvestment extends GUI_Module
{
    protected array $templates = [
        'stdout' => 'tpl_PvInvestment.html',
    ];

    protected function prepare(): void
    {
        $calculator = new ScenarioCalculator();
        $scenarios = DemoScenarioFactory::comparisonScenarios();
        $primaryResult = $calculator->calculate($scenarios[0]);
        $comparison = $calculator->compare($scenarios);

        $this->Template->setVars([
            'MODULE_ID' => $this->getName(),
            'PRIMARY_SCENARIO_TITLE' => $this->escape($primaryResult->description),
            'PRIMARY_SCENARIO_ASSUMPTIONS' => $this->renderScenarioAssumptions($scenarios[0]),
            'DASHBOARD_CARDS' => $this->renderDashboardCards($primaryResult),
            'COMPARISON_ROWS' => $this->renderComparisonRows($comparison->results),
            'YEAR_ROWS' => $this->renderYearRows($primaryResult),
            'MONTH_ROWS' => $this->renderMonthRows($primaryResult),
        ]);
    }

    private function renderScenarioAssumptions(ScenarioInput $scenario): string
    {
        return sprintf(
            'Laufzeit %d Jahre, Steuersatz %s, Batterie-Degradation %s p.a.',
            $scenario->durationYears,
            $this->formatPercent($scenario->taxAssumptions->incomeTaxRate),
            $this->formatPercent($scenario->batteryModel->batteryDegradationRatePerYear),
        );
    }

    private function renderDashboardCards(ScenarioResult $result): string
    {
        $batteryCapexInvestor = $this->sumYearResults($result->yearlyResults, 'batteryCapexInvestor')
            + $this->sumYearResults($result->yearlyResults, 'batteryReplacementCapexInvestor');

        $cards = [
            ['Kumulierter Investor-Cashflow', $this->formatEuro($result->cumulativeInvestorCashflow), $result->cumulativeInvestorCashflow],
            ['Kumulierter Steuer-Cashflow', $this->formatEuro($result->cumulativeTaxCashflow), $result->cumulativeTaxCashflow],
            ['Kumulierte Batterieerloese Investor', $this->formatEuro($result->cumulativeInvestorBatteryRevenue), $result->cumulativeInvestorBatteryRevenue],
            ['Batterie-Capex Investor', $this->formatEuro($batteryCapexInvestor), -$batteryCapexInvestor],
            ['Sparplan-Endwert', $this->formatEuro($result->savingsPlanEndingCapital), $result->savingsPlanEndingCapital],
            ['Gesamtergebnis Investor', $this->formatEuro($result->totalInvestorResult), $result->totalInvestorResult],
            ['Break-even-Jahr', $result->breakEvenYear !== null ? (string)$result->breakEvenYear : 'nicht erreicht', $result->breakEvenYear !== null ? 1.0 : -1.0],
        ];

        $html = '';
        foreach($cards as [$label, $value, $numericValue]) {
            $class = $numericValue < 0.0 ? ' pvi-kpi--negative' : '';
            $html .= '<article class="pvi-kpi'.$class.'">';
            $html .= '<span>'.$this->escape($label).'</span>';
            $html .= '<strong>'.$this->escape($value).'</strong>';
            $html .= '</article>';
        }

        return $html;
    }

    /**
     * @param list<ScenarioResult> $results
     */
    private function renderComparisonRows(array $results): string
    {
        $html = '';
        $base = $results[0] ?? null;
        foreach($results as $result) {
            $batteryCapexInvestor = $this->sumYearResults($result->yearlyResults, 'batteryCapexInvestor')
                + $this->sumYearResults($result->yearlyResults, 'batteryReplacementCapexInvestor');
            $differenceToBase = $base !== null ? $result->totalInvestorResult - $base->totalInvestorResult : 0.0;

            $html .= '<tr>';
            $html .= '<th scope="row">'.$this->escape($result->description).'</th>';
            $html .= '<td>'.$this->formatEuro($result->cumulativeInvestorCashflow).'</td>';
            $html .= '<td>'.$this->formatEuro($result->cumulativeTaxCashflow).'</td>';
            $html .= '<td>'.$this->formatEuro($result->cumulativeInvestorBatteryRevenue).'</td>';
            $html .= '<td>'.$this->formatEuro($batteryCapexInvestor).'</td>';
            $html .= '<td>'.$this->formatEuro($result->savingsPlanEndingCapital).'</td>';
            $html .= '<td>'.$this->formatEuro($result->totalInvestorResult).'</td>';
            $html .= '<td>'.($result->breakEvenYear !== null ? (string)$result->breakEvenYear : '-').'</td>';
            $html .= '<td>'.$this->formatEuro($differenceToBase).'</td>';
            $html .= '</tr>';
        }

        return $html;
    }

    private function renderYearRows(ScenarioResult $result): string
    {
        $html = '';
        $cumulativeInvestorCashflow = 0.0;
        foreach($result->yearlyResults as $yearResult) {
            $cumulativeInvestorCashflow += $yearResult->investorCashflowBeforeSavings;

            $html .= '<tr>';
            $html .= '<th scope="row">'.(string)$yearResult->year.'</th>';
            $html .= '<td>'.$this->formatEuro($yearResult->pvRevenue).'</td>';
            $html .= '<td>'.$this->formatEuro($yearResult->batteryInvestorRevenue).'</td>';
            $html .= '<td>'.$this->formatEuro($yearResult->batteryInvestorCosts).'</td>';
            $html .= '<td>'.$this->formatEuro($yearResult->batteryCapexInvestor).'</td>';
            $html .= '<td>'.$this->formatEuro($yearResult->batteryReplacementCapexInvestor).'</td>';
            $html .= '<td>'.$this->formatEuro($yearResult->financingInterest).'</td>';
            $html .= '<td>'.$this->formatEuro($yearResult->financingPrincipal).'</td>';
            $html .= '<td>'.$this->formatEuro($yearResult->taxCashflow).'</td>';
            $html .= '<td>'.$this->formatEuro($yearResult->savingsEndValue).'</td>';
            $html .= '<td>'.$this->formatEuro($yearResult->investorCashflowBeforeSavings).'</td>';
            $html .= '<td>'.$this->formatEuro($cumulativeInvestorCashflow).'</td>';
            $html .= '</tr>';
        }

        return $html;
    }

    private function renderMonthRows(ScenarioResult $result): string
    {
        $html = '';
        foreach($result->monthlyResults as $monthResult) {
            $html .= '<tr>';
            $html .= '<th scope="row">'.sprintf('%04d-%02d', $monthResult->year, $monthResult->month).'</th>';
            $html .= '<td>'.$this->formatEuro($monthResult->pvRevenue).'</td>';
            $html .= '<td>'.$this->formatEuro($monthResult->batteryInvestorRevenue).'</td>';
            $html .= '<td>'.$this->formatEuro($monthResult->batteryInvestorCosts).'</td>';
            $html .= '<td>'.$this->formatEuro($monthResult->batteryCapexInvestor).'</td>';
            $html .= '<td>'.$this->formatEuro($monthResult->batteryReplacementCapexInvestor).'</td>';
            $html .= '<td>'.$this->formatEuro($monthResult->taxCashflow).'</td>';
            $html .= '<td>'.$this->formatEuro($monthResult->investorCashflowBeforeSavings).'</td>';
            $html .= '</tr>';
        }

        return $html;
    }

    /**
     * @param list<YearResult> $yearResults
     */
    private function sumYearResults(array $yearResults, string $property): float
    {
        $sum = 0.0;
        foreach($yearResults as $yearResult) {
            $sum += $yearResult->{$property};
        }

        return $sum;
    }

    private function formatEuro(float $amount): string
    {
        return number_format($amount, 2, ',', '.').' EUR';
    }

    private function formatPercent(float $rate): string
    {
        return number_format($rate * 100.0, 2, ',', '.').' %';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
