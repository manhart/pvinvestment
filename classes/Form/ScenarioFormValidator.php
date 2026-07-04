<?php
declare(strict_types=1);

namespace pvinvestment\classes\Form;

use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\RevenueSharingModel;
use pvinvestment\classes\Domain\Tax\TaxLossHandlingStrategy;
use pvinvestment\classes\Domain\TaxAssumptions;

final class ScenarioFormValidator
{
    /**
     * @return array<string, string>
     */
    public function validate(ScenarioFormData $data): array
    {
        $errors = [];

        if($data->string('scenarioName') === '') {
            $errors['scenarioName'] = 'Bitte einen Szenarionamen angeben.';
        }

        $this->validateIntegerRange($data, $errors, 'startYear', 2000, 2100, 'Startjahr');
        $this->validateIntegerRange($data, $errors, 'durationYears', 1, 50, 'Laufzeit');

        foreach([
            'pvCapacityKwp' => 'Anlagenleistung',
            'pvSpecificYieldKwhPerKwp' => 'Spezifischer Jahresertrag',
            'pvAnnualRevenue' => 'PV-Erloes pro Jahr',
            'pvOperatingCosts' => 'PV-Betriebskosten',
            'batteryGrossRevenue' => 'Batterie-Bruttoerloes',
            'marketAccessFee' => 'Market Access Fee',
            'optimizerFee' => 'Optimizer Fee',
            'batteryOpex' => 'Batterie-OPEX',
            'batteryCapex' => 'Batterie-Capex',
            'batteryReplacementCost' => 'Ersatzkosten',
            'debtAmount' => 'Fremdkapitalbetrag',
            'annualRepayment' => 'Jaehrliche Tilgung',
            'acquisitionCost' => 'Anschaffungskosten',
            'brokerageFee' => 'Maklercourtage',
            'savingsStartingCapital' => 'Sparplan-Startkapital',
            'savingsMonthlyContribution' => 'Monatliche Sparplan-Einzahlung',
            'savingsAnnualContribution' => 'Jaehrliche Sparplan-Einzahlung',
        ] as $field => $label) {
            $this->validateNonNegativeNumber($data, $errors, $field, $label);
        }

        foreach([
            'pvOperatingCostEscalationPercent' => 'Betriebskostensteigerung',
            'investorRevenueSharePercent' => 'Investor Revenue Share',
            'investorCostSharePercent' => 'Investor Cost Share',
            'investorCapexSharePercent' => 'Investor Capex Share',
            'batteryDegradationPercent' => 'Batterie-Degradation',
            'investorReplacementCostSharePercent' => 'Investor Replacement Cost Share',
            'interestRatePercent' => 'Zinssatz',
            'incomeTaxRatePercent' => 'Einkommensteuersatz',
            'iabRatePercent' => 'IAB-Satz',
            'specialDepreciationRatePercent' => 'Sonder-AfA-Satz',
            'linearDepreciationRatePercent' => 'Lineare AfA',
            'decliningDepreciationRatePercent' => 'Degressive AfA',
            'savingsReinvestmentRatePercent' => 'Reinvestitionsquote',
        ] as $field => $label) {
            $this->validatePercent($data, $errors, $field, $label);
        }

        foreach([
            'capexPaymentMonth' => 'Capex-Zahlungsmonat',
            'batteryReplacementMonth' => 'Ersatzmonat',
            'investmentMonth' => 'Investitionsmonat',
            'depreciationStartMonth' => 'AfA-Beginn-Monat',
            'eegCommissioningMonth' => 'EEG-Inbetriebnahme-Monat',
            'gridConnectionMonth' => 'Netzanschluss-Monat',
            'revenueStartMonth' => 'Ertragsbeginn-Monat',
            'interestStartMonth' => 'Zinsbeginn-Monat',
            'repaymentStartMonth' => 'Tilgungsbeginn-Monat',
            'savingsContributionStartMonth' => 'Sparplan-Startmonat',
            'annualSavingsContributionMonth' => 'Jaehrlicher Sparplanmonat',
        ] as $field => $label) {
            $this->validateIntegerRange($data, $errors, $field, 1, 12, $label);
        }

        foreach([
            'capexPaymentYear' => 'Capex-Zahlungsjahr',
            'batteryReplacementYear' => 'Ersatzjahr',
            'investmentYear' => 'Investitionsjahr',
            'depreciationStartYear' => 'AfA-Beginn-Jahr',
            'eegCommissioningYear' => 'EEG-Inbetriebnahme-Jahr',
            'gridConnectionYear' => 'Netzanschluss-Jahr',
            'revenueStartYear' => 'Ertragsbeginn-Jahr',
            'interestStartYear' => 'Zinsbeginn-Jahr',
            'repaymentStartYear' => 'Tilgungsbeginn-Jahr',
            'savingsContributionStartYear' => 'Sparplan-Startjahr',
        ] as $field => $label) {
            $this->validateIntegerRange($data, $errors, $field, 2000, 2100, $label);
        }

        $this->validateIntegerRange($data, $errors, 'taxPaymentDelayMonths', 0, 240, 'Steuerzahlungsversatz');

        $this->validateAllowed($data, $errors, 'batteryModel', [
            BatteryModel::MODEL_NONE,
            BatteryModel::MODEL_FULL_OWNERSHIP,
            BatteryModel::MODEL_PROFIT_SHARING,
        ], 'Batteriemodell');
        $this->validateAllowed($data, $errors, 'sharingBase', [
            RevenueSharingModel::SHARING_BASE_GROSS_REVENUE,
            RevenueSharingModel::SHARING_BASE_NET_REVENUE,
            RevenueSharingModel::SHARING_BASE_NET_MARGIN,
        ], 'Sharing Base');
        $this->validateAllowed($data, $errors, 'depreciationMethod', [
            TaxAssumptions::DEPRECIATION_LINEAR,
            TaxAssumptions::DEPRECIATION_DECLINING,
        ], 'AfA-Methode');
        $this->validateAllowed($data, $errors, 'lossHandlingStrategy', [
            TaxLossHandlingStrategy::IMMEDIATE,
            TaxLossHandlingStrategy::CARRY_FORWARD,
            TaxLossHandlingStrategy::CARRY_BACK,
            TaxLossHandlingStrategy::MANUAL,
            TaxLossHandlingStrategy::NONE,
        ], 'Verlustnutzung');
        $this->validateAllowed($data, $errors, 'brokerageTreatment', [
            'capitalize',
            'immediate',
            'ignore',
        ], 'Behandlung Maklercourtage');

        $this->validateYearsWithinScenario($data, $errors);

        return $errors;
    }

    /**
     * @param array<string, string> $errors
     */
    private function validateNonNegativeNumber(ScenarioFormData $data, array &$errors, string $field, string $label): void
    {
        if(!$this->isNumeric($data->string($field))) {
            $errors[$field] = $label.' muss numerisch sein.';
            return;
        }
        if($data->float($field) < 0.0) {
            $errors[$field] = $label.' darf nicht negativ sein.';
        }
    }

    /**
     * @param array<string, string> $errors
     */
    private function validatePercent(ScenarioFormData $data, array &$errors, string $field, string $label): void
    {
        if(!$this->isNumeric($data->string($field))) {
            $errors[$field] = $label.' muss numerisch sein.';
            return;
        }
        $value = $data->float($field);
        if($value < 0.0 || $value > 100.0) {
            $errors[$field] = $label.' muss zwischen 0 und 100 Prozent liegen.';
        }
    }

    /**
     * @param array<string, string> $errors
     */
    private function validateIntegerRange(ScenarioFormData $data, array &$errors, string $field, int $min, int $max, string $label): void
    {
        if(!$this->isInteger($data->string($field))) {
            $errors[$field] = $label.' muss eine ganze Zahl sein.';
            return;
        }
        $value = $data->int($field);
        if($value < $min || $value > $max) {
            $errors[$field] = $label.' muss zwischen '.$min.' und '.$max.' liegen.';
        }
    }

    /**
     * @param array<string, string> $errors
     * @param list<string> $allowed
     */
    private function validateAllowed(ScenarioFormData $data, array &$errors, string $field, array $allowed, string $label): void
    {
        if(!in_array($data->string($field), $allowed, true)) {
            $errors[$field] = $label.' enthaelt einen nicht unterstuetzten Wert.';
        }
    }

    /**
     * @param array<string, string> $errors
     */
    private function validateYearsWithinScenario(ScenarioFormData $data, array &$errors): void
    {
        if(isset($errors['startYear']) || isset($errors['durationYears'])) {
            return;
        }

        $startYear = $data->int('startYear');
        $endYear = $startYear + $data->int('durationYears') - 1;
        foreach([
            'capexPaymentYear' => 'Capex-Zahlungsjahr',
            'batteryReplacementYear' => 'Ersatzjahr',
            'revenueStartYear' => 'Ertragsbeginn-Jahr',
            'interestStartYear' => 'Zinsbeginn-Jahr',
            'repaymentStartYear' => 'Tilgungsbeginn-Jahr',
            'savingsContributionStartYear' => 'Sparplan-Startjahr',
        ] as $field => $label) {
            if(isset($errors[$field])) {
                continue;
            }
            $year = $data->int($field);
            if($year < $startYear || $year > $endYear) {
                $errors[$field] = $label.' muss innerhalb der Szenariolaufzeit '.$startYear.' bis '.$endYear.' liegen.';
            }
        }
    }

    private function isNumeric(string $value): bool
    {
        return $value !== '' && is_numeric(str_replace(',', '.', $value));
    }

    private function isInteger(string $value): bool
    {
        return preg_match('/^\d+$/', $value) === 1;
    }
}
