<?php
declare(strict_types=1);

namespace pvinvestment\guis\GUI_PvInvestment;

use pool\classes\GUI\GUI_Module;
use pvinvestment\classes\Calculators\ScenarioCalculator;
use pvinvestment\classes\Demo\DemoScenarioFactory;
use pvinvestment\classes\Domain\Results\YearResult;
use pvinvestment\classes\Form\ScenarioFormData;
use pvinvestment\classes\Form\ScenarioFormMapper;
use pvinvestment\classes\Form\ScenarioFormValidator;
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
        $formData = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
            ? ScenarioFormData::fromPost(is_array($_POST['scenario'] ?? null) ? $_POST['scenario'] : [])
            : ScenarioFormData::defaults();
        $formErrors = (new ScenarioFormValidator())->validate($formData);

        $scenarios = DemoScenarioFactory::comparisonScenarios();
        $primaryScenario = $formErrors === []
            ? (new ScenarioFormMapper())->map($formData)
            : $scenarios[0];
        $primaryResult = $calculator->calculate($primaryScenario);
        $comparison = $calculator->compare($scenarios);

        $this->Template->setVars([
            'MODULE_ID' => $this->getName(),
            'FORM_ERRORS' => $this->renderFormErrors($formErrors),
            'FORM_HTML' => $this->renderForm($formData, $formErrors),
            'PRIMARY_SCENARIO_TITLE' => $this->escape($primaryResult->description),
            'PRIMARY_SCENARIO_ASSUMPTIONS' => $this->renderScenarioAssumptions($primaryScenario),
            'DASHBOARD_CARDS' => $this->renderDashboardCards($primaryResult),
            'COMPARISON_ROWS' => $this->renderComparisonRows($comparison->results),
            'YEAR_ROWS' => $this->renderYearRows($primaryResult),
            'MONTH_ROWS' => $this->renderMonthRows($primaryResult),
        ]);
    }

    /**
     * @param array<string, string> $errors
     */
    private function renderFormErrors(array $errors): string
    {
        if($errors === []) {
            return '';
        }

        $html = '<div class="pvi-alert" role="alert"><strong>Bitte Eingaben pruefen.</strong><ul>';
        foreach($errors as $message) {
            $html .= '<li>'.$this->escape($message).'</li>';
        }

        return $html.'</ul></div>';
    }

    /**
     * @param array<string, string> $errors
     */
    private function renderForm(ScenarioFormData $data, array $errors): string
    {
        return $this->renderFieldset('Projektdaten', [
            $this->renderInput($data, $errors, 'scenarioName', 'Szenarioname'),
            $this->renderInput($data, $errors, 'startYear', 'Startjahr', 'number'),
            $this->renderInput($data, $errors, 'durationYears', 'Laufzeit in Jahren', 'number'),
        ])
        .$this->renderFieldset('PV-Anlage', [
            $this->renderInput($data, $errors, 'pvCapacityKwp', 'Anlagenleistung kWp', 'number', '0.01'),
            $this->renderInput($data, $errors, 'pvSpecificYieldKwhPerKwp', 'Spezifischer Jahresertrag kWh/kWp', 'number', '0.01'),
            $this->renderInput($data, $errors, 'pvAnnualRevenue', 'PV-Erloes pro Jahr', 'number', '0.01'),
            $this->renderInput($data, $errors, 'pvOperatingCosts', 'Laufende Betriebskosten', 'number', '0.01'),
            $this->renderInput($data, $errors, 'pvOperatingCostEscalationPercent', 'Betriebskostensteigerung %', 'number', '0.01'),
        ], 'Aktuell verwendet das Domain-Modell den eingegebenen PV-Jahreserloes direkt; kWp, spezifischer Ertrag und Kostensteigerung bleiben Eingabekontext.')
        .$this->renderFieldset('Batterie', [
            $this->renderSelect($data, $errors, 'batteryModel', 'Batteriemodell', [
                'none' => 'keine Batterie',
                'full_ownership' => 'Vollerwerb',
                'profit_sharing' => 'Profit-Sharing',
            ]),
            $this->renderInput($data, $errors, 'batteryGrossRevenue', 'Batterie-Bruttoerloes', 'number', '0.01'),
            $this->renderInput($data, $errors, 'marketAccessFee', 'Market Access Fee', 'number', '0.01'),
            $this->renderInput($data, $errors, 'optimizerFee', 'Optimizer Fee', 'number', '0.01'),
            $this->renderInput($data, $errors, 'batteryOpex', 'Batterie-OPEX', 'number', '0.01'),
            $this->renderSelect($data, $errors, 'sharingBase', 'Sharing Base', [
                'gross_revenue' => 'gross_revenue',
                'net_revenue' => 'net_revenue',
                'net_margin' => 'net_margin',
            ]),
            $this->renderInput($data, $errors, 'investorRevenueSharePercent', 'Investor Revenue Share %', 'number', '0.01'),
            $this->renderInput($data, $errors, 'investorCostSharePercent', 'Investor Cost Share %', 'number', '0.01'),
            $this->renderInput($data, $errors, 'batteryCapex', 'Batterie-Capex', 'number', '0.01'),
            $this->renderInput($data, $errors, 'investorCapexSharePercent', 'Investor Capex Share %', 'number', '0.01'),
            $this->renderInput($data, $errors, 'capexPaymentYear', 'Capex-Zahlungsjahr', 'number'),
            $this->renderInput($data, $errors, 'capexPaymentMonth', 'Capex-Zahlungsmonat', 'number'),
            $this->renderInput($data, $errors, 'batteryDegradationPercent', 'Degradation p.a. %', 'number', '0.01'),
            $this->renderCheckbox($data, 'batteryReplacementEnabled', 'Ersatzinvestition aktiv'),
            $this->renderInput($data, $errors, 'batteryReplacementYear', 'Ersatzjahr', 'number'),
            $this->renderInput($data, $errors, 'batteryReplacementMonth', 'Ersatzmonat', 'number'),
            $this->renderInput($data, $errors, 'batteryReplacementCost', 'Ersatzkosten', 'number', '0.01'),
            $this->renderInput($data, $errors, 'investorReplacementCostSharePercent', 'Investor Replacement Cost Share %', 'number', '0.01'),
        ])
        .$this->renderFieldset('Finanzierung', [
            $this->renderInput($data, $errors, 'debtAmount', 'Fremdkapitalbetrag', 'number', '0.01'),
            $this->renderInput($data, $errors, 'interestRatePercent', 'Zinssatz p.a. %', 'number', '0.01'),
            $this->renderInput($data, $errors, 'annualRepayment', 'Jaehrliche Tilgung', 'number', '0.01'),
        ], 'Der aktuelle Finanzierungsprototyp nutzt daraus Jahreszins und Jahres-Tilgung.')
        .$this->renderFieldset('Steuer und Kosten', [
            $this->renderInput($data, $errors, 'incomeTaxRatePercent', 'Einkommensteuersatz %', 'number', '0.01'),
            $this->renderCheckbox($data, 'iabEnabled', 'IAB aktiv'),
            $this->renderInput($data, $errors, 'iabRatePercent', 'IAB-Satz %', 'number', '0.01'),
            $this->renderCheckbox($data, 'specialDepreciationEnabled', 'Sonder-AfA aktiv'),
            $this->renderInput($data, $errors, 'specialDepreciationRatePercent', 'Sonder-AfA-Satz %', 'number', '0.01'),
            $this->renderSelect($data, $errors, 'depreciationMethod', 'AfA-Methode', [
                'linear' => 'linear',
                'declining' => 'degressiv',
            ]),
            $this->renderInput($data, $errors, 'linearDepreciationRatePercent', 'Lineare AfA %', 'number', '0.01'),
            $this->renderInput($data, $errors, 'decliningDepreciationRatePercent', 'Degressive AfA %', 'number', '0.01'),
            $this->renderSelect($data, $errors, 'lossHandlingStrategy', 'Verlustnutzung', [
                'immediate' => 'immediate',
                'carry_forward' => 'carry_forward',
                'carry_back' => 'carry_back',
                'manual' => 'manual',
                'none' => 'none',
            ]),
            $this->renderInput($data, $errors, 'taxPaymentDelayMonths', 'Steuerzahlungsversatz in Monaten', 'number'),
            $this->renderInput($data, $errors, 'acquisitionCost', 'Anschaffungskosten', 'number', '0.01'),
            $this->renderInput($data, $errors, 'brokerageFee', 'Maklercourtage / Vermittlungsprovision', 'number', '0.01'),
            $this->renderSelect($data, $errors, 'brokerageTreatment', 'Behandlung Maklercourtage', [
                'capitalize' => 'Anschaffungsnebenkosten, aktivieren',
                'immediate' => 'sofort abzugsfaehig',
                'ignore' => 'nicht beruecksichtigen',
            ]),
            $this->renderCheckbox($data, 'brokerageIabEligible', 'Maklercourtage IAB-beguenstigt'),
        ])
        .$this->renderFieldset('Timing', [
            $this->renderInput($data, $errors, 'investmentYear', 'Investitionsjahr', 'number'),
            $this->renderInput($data, $errors, 'investmentMonth', 'Investitionsmonat', 'number'),
            $this->renderInput($data, $errors, 'depreciationStartYear', 'AfA-Beginn Jahr', 'number'),
            $this->renderInput($data, $errors, 'depreciationStartMonth', 'AfA-Beginn Monat', 'number'),
            $this->renderInput($data, $errors, 'eegCommissioningYear', 'EEG-Inbetriebnahme Jahr', 'number'),
            $this->renderInput($data, $errors, 'eegCommissioningMonth', 'EEG-Inbetriebnahme Monat', 'number'),
            $this->renderInput($data, $errors, 'gridConnectionYear', 'Netzanschluss Jahr', 'number'),
            $this->renderInput($data, $errors, 'gridConnectionMonth', 'Netzanschluss Monat', 'number'),
            $this->renderInput($data, $errors, 'revenueStartYear', 'Ertragsbeginn Jahr', 'number'),
            $this->renderInput($data, $errors, 'revenueStartMonth', 'Ertragsbeginn Monat', 'number'),
            $this->renderInput($data, $errors, 'interestStartYear', 'Zinsbeginn Jahr', 'number'),
            $this->renderInput($data, $errors, 'interestStartMonth', 'Zinsbeginn Monat', 'number'),
            $this->renderInput($data, $errors, 'repaymentStartYear', 'Tilgungsbeginn Jahr', 'number'),
            $this->renderInput($data, $errors, 'repaymentStartMonth', 'Tilgungsbeginn Monat', 'number'),
        ])
        .$this->renderFieldset('Sparplan', [
            $this->renderInput($data, $errors, 'savingsStartingCapital', 'Startkapital', 'number', '0.01'),
            $this->renderInput($data, $errors, 'savingsMonthlyContribution', 'Monatliche Einzahlung', 'number', '0.01'),
            $this->renderInput($data, $errors, 'savingsAnnualContribution', 'Jaehrliche Einzahlung', 'number', '0.01'),
            $this->renderInput($data, $errors, 'savingsReinvestmentRatePercent', 'Reinvestitionsquote aus positivem Cashflow %', 'number', '0.01'),
            $this->renderInput($data, $errors, 'savingsContributionStartYear', 'Sparplan-Startjahr', 'number'),
            $this->renderInput($data, $errors, 'savingsContributionStartMonth', 'Sparplan-Startmonat', 'number'),
            $this->renderInput($data, $errors, 'annualSavingsContributionMonth', 'Monat der jaehrlichen Einzahlung', 'number'),
        ], 'Erwartete Rendite, Kostenquote und Kapitalertragsteuer sind im aktuellen Sparplan-Prototyp noch nicht produktiv modelliert.');
    }

    /**
     * @param list<string> $fields
     */
    private function renderFieldset(string $legend, array $fields, string $note = ''): string
    {
        $html = '<fieldset class="pvi-form__fieldset">';
        $html .= '<legend>'.$this->escape($legend).'</legend>';
        if($note !== '') {
            $html .= '<p class="pvi-form__note">'.$this->escape($note).'</p>';
        }
        $html .= '<div class="pvi-form__grid">'.implode('', $fields).'</div>';

        return $html.'</fieldset>';
    }

    /**
     * @param array<string, string> $errors
     */
    private function renderInput(
        ScenarioFormData $data,
        array $errors,
        string $field,
        string $label,
        string $type = 'text',
        string $step = '1',
    ): string {
        $id = $this->getName().'_'.$field;
        $html = '<label class="pvi-form__field" for="'.$this->escape($id).'">';
        $html .= '<span>'.$this->escape($label).'</span>';
        $html .= '<input id="'.$this->escape($id).'" name="scenario['.$this->escape($field).']" type="'.$this->escape($type).'"';
        if($type === 'number') {
            $html .= ' step="'.$this->escape($step).'"';
        }
        $html .= ' value="'.$this->escape($data->string($field)).'">';
        $html .= $this->renderFieldError($errors, $field);

        return $html.'</label>';
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, string> $options
     */
    private function renderSelect(ScenarioFormData $data, array $errors, string $field, string $label, array $options): string
    {
        $id = $this->getName().'_'.$field;
        $html = '<label class="pvi-form__field" for="'.$this->escape($id).'">';
        $html .= '<span>'.$this->escape($label).'</span>';
        $html .= '<select id="'.$this->escape($id).'" name="scenario['.$this->escape($field).']">';
        foreach($options as $value => $caption) {
            $selected = $data->string($field) === $value ? ' selected' : '';
            $html .= '<option value="'.$this->escape($value).'"'.$selected.'>'.$this->escape($caption).'</option>';
        }
        $html .= '</select>';
        $html .= $this->renderFieldError($errors, $field);

        return $html.'</label>';
    }

    private function renderCheckbox(ScenarioFormData $data, string $field, string $label): string
    {
        $id = $this->getName().'_'.$field;
        $checked = $data->bool($field) ? ' checked' : '';
        $html = '<input type="hidden" name="scenario['.$this->escape($field).']" value="0">';
        $html .= '<label class="pvi-form__field pvi-form__field--checkbox" for="'.$this->escape($id).'">';
        $html .= '<input id="'.$this->escape($id).'" name="scenario['.$this->escape($field).']" type="checkbox" value="1"'.$checked.'>';
        $html .= '<span>'.$this->escape($label).'</span>';

        return $html.'</label>';
    }

    /**
     * @param array<string, string> $errors
     */
    private function renderFieldError(array $errors, string $field): string
    {
        if(!isset($errors[$field])) {
            return '';
        }

        return '<em class="pvi-form__error">'.$this->escape($errors[$field]).'</em>';
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
