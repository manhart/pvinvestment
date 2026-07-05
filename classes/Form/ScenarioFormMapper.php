<?php
declare(strict_types=1);

namespace pvinvestment\classes\Form;

use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\FinancingAssumptions;
use pvinvestment\classes\Domain\ProjectTimingAssumptions;
use pvinvestment\classes\Domain\PvAssumptions;
use pvinvestment\classes\Domain\RevenueSharingModel;
use pvinvestment\classes\Domain\SavingsPlanAssumptions;
use pvinvestment\classes\Domain\Scenario\ScenarioInput;
use pvinvestment\classes\Domain\TaxAssumptions;

final class ScenarioFormMapper
{
    public function map(ScenarioFormData $data): ScenarioInput
    {
        $timing = new ProjectTimingAssumptions(
            calculationYear: $data->int('startYear'),
            investmentYear: $data->int('investmentYear'),
            investmentMonth: $data->int('investmentMonth'),
            depreciationStartYear: $data->int('depreciationStartYear'),
            depreciationStartMonth: $data->int('depreciationStartMonth'),
            eegCommissioningYear: $data->int('eegCommissioningYear'),
            eegCommissioningMonth: $data->int('eegCommissioningMonth'),
            gridConnectionYear: $data->int('gridConnectionYear'),
            gridConnectionMonth: $data->int('gridConnectionMonth'),
            revenueStartYear: $data->int('revenueStartYear'),
            revenueStartMonth: $data->int('revenueStartMonth'),
            interestStartYear: $data->int('interestStartYear'),
            interestStartMonth: $data->int('interestStartMonth'),
            repaymentStartYear: $data->int('repaymentStartYear'),
            repaymentStartMonth: $data->int('repaymentStartMonth'),
            taxPaymentYear: $data->int('startYear'),
            savingsPlanContributionStartYear: $data->int('savingsContributionStartYear'),
            savingsPlanContributionStartMonth: $data->int('savingsContributionStartMonth'),
            annualSavingsPlanContributionMonth: $data->int('annualSavingsContributionMonth'),
        );

        $brokerageAllocation = $this->brokerageAllocation($data);

        return new ScenarioInput(
            id: 'form_scenario',
            description: $data->string('scenarioName'),
            pvAssumptions: new PvAssumptions(
                annualRevenue: 0.0,
                annualOperatingCosts: $data->float('pvOperatingCosts'),
                installedCapacityKwp: $data->float('pvCapacityKwp'),
                specificYieldKwhPerKwp: $data->float('pvSpecificYieldKwhPerKwp'),
                pvDegradationRatePerYear: $data->percentRate('pvDegradationPercent'),
                pvAvailabilityRate: $data->percentRate('pvAvailabilityPercent'),
                pvCurtailmentRate: $data->percentRate('pvCurtailmentPercent'),
                electricityPriceCentsPerKwh: $data->float('electricityPriceCentsPerKwh'),
                electricityPriceEscalationRatePerYear: $data->percentRate('electricityPriceEscalationPercent'),
                directMarketingCostCentsPerKwh: $data->float('directMarketingCostCentsPerKwh'),
                otherRevenueDeductionRate: $data->percentRate('otherRevenueDeductionPercent'),
                manualPvAnnualRevenueOverride: $data->nullableFloat('manualPvAnnualRevenueOverride'),
            ),
            batteryModel: $this->batteryModel($data),
            revenueSharingModel: $this->sharingModel($data),
            financingAssumptions: new FinancingAssumptions(
                annualInterest: $data->float('debtAmount') * $data->percentRate('interestRatePercent'),
                annualRepayment: $data->float('annualRepayment'),
            ),
            taxAssumptions: new TaxAssumptions(
                calculationYear: $data->int('startYear'),
                incomeTaxRate: $data->percentRate('incomeTaxRatePercent'),
                acquisitionCost: $data->float('acquisitionCost'),
                capitalizableAncillaryCosts: $brokerageAllocation['capitalizable'],
                immediatelyDeductibleCosts: $brokerageAllocation['immediate'],
                depreciationStartYear: $data->int('depreciationStartYear'),
                depreciationStartMonth: $data->int('depreciationStartMonth'),
                depreciationMethod: $data->string('depreciationMethod'),
                linearDepreciationRate: $data->percentRate('linearDepreciationRatePercent'),
                decliningDepreciationRate: $data->percentRate('decliningDepreciationRatePercent'),
                iabEnabled: $data->bool('iabEnabled'),
                iabRate: $data->percentRate('iabRatePercent'),
                iabEligibleAcquisitionCost: $data->float('acquisitionCost'),
                iabEligibleCapitalizableAncillaryCosts: $brokerageAllocation['iabEligibleCapitalizable'],
                iabDeductionYear: $data->int('startYear'),
                iabAdditionYear: $data->int('startYear') + 1,
                specialDepreciationEnabled: $data->bool('specialDepreciationEnabled'),
                specialDepreciationRate: $data->percentRate('specialDepreciationRatePercent'),
                specialDepreciationStartYear: $data->int('depreciationStartYear'),
                specialDepreciationYears: 1,
                lossHandlingStrategy: $data->string('lossHandlingStrategy'),
                taxPaymentDelayMonths: $data->int('taxPaymentDelayMonths'),
            ),
            savingsPlanAssumptions: new SavingsPlanAssumptions(
                startingCapital: $data->float('savingsStartingCapital'),
                monthlyContribution: $data->float('savingsMonthlyContribution'),
                annualContribution: $data->float('savingsAnnualContribution'),
                positiveCashflowReinvestmentRate: $data->percentRate('savingsReinvestmentRatePercent'),
            ),
            durationYears: $data->int('durationYears'),
            timingAssumptions: $timing,
        );
    }

    private function batteryModel(ScenarioFormData $data): BatteryModel
    {
        if($data->string('batteryModel') === BatteryModel::MODEL_NONE) {
            return BatteryModel::none();
        }

        $common = [
            'annualRevenue' => $data->float('batteryGrossRevenue'),
            'annualOperatingCosts' => $data->float('batteryOpex'),
            'marketAccessFee' => $data->float('marketAccessFee'),
            'optimizerFee' => $data->float('optimizerFee'),
            'batteryCapex' => $data->float('batteryCapex'),
            'capexPaymentYear' => $data->int('capexPaymentYear'),
            'capexPaymentMonth' => $data->int('capexPaymentMonth'),
            'batteryDegradationRatePerYear' => $data->percentRate('batteryDegradationPercent'),
            'batteryReplacementEnabled' => $data->bool('batteryReplacementEnabled'),
            'batteryReplacementYear' => $data->int('batteryReplacementYear'),
            'batteryReplacementMonth' => $data->int('batteryReplacementMonth'),
            'batteryReplacementCost' => $data->float('batteryReplacementCost'),
            'investorReplacementCostShare' => $data->percentRate('investorReplacementCostSharePercent'),
            'operatorReplacementCostShare' => 1.0 - $data->percentRate('investorReplacementCostSharePercent'),
        ];

        if($data->string('batteryModel') === BatteryModel::MODEL_FULL_OWNERSHIP) {
            return BatteryModel::fullOwnership(...$common);
        }

        return BatteryModel::profitSharing(
            annualRevenue: $common['annualRevenue'],
            annualOperatingCosts: $common['annualOperatingCosts'],
            sharingModel: $this->sharingModel($data),
            marketAccessFee: $common['marketAccessFee'],
            optimizerFee: $common['optimizerFee'],
            batteryCapex: $common['batteryCapex'],
            capexPaymentYear: $common['capexPaymentYear'],
            capexPaymentMonth: $common['capexPaymentMonth'],
            batteryDegradationRatePerYear: $common['batteryDegradationRatePerYear'],
            batteryReplacementEnabled: $common['batteryReplacementEnabled'],
            batteryReplacementYear: $common['batteryReplacementYear'],
            batteryReplacementMonth: $common['batteryReplacementMonth'],
            batteryReplacementCost: $common['batteryReplacementCost'],
            investorReplacementCostShare: $common['investorReplacementCostShare'],
            operatorReplacementCostShare: $common['operatorReplacementCostShare'],
        );
    }

    private function sharingModel(ScenarioFormData $data): RevenueSharingModel
    {
        if($data->string('batteryModel') === BatteryModel::MODEL_FULL_OWNERSHIP
            || $data->string('batteryModel') === BatteryModel::MODEL_NONE) {
            return RevenueSharingModel::fullInvestorOwnership();
        }

        $investorRevenueShare = $data->percentRate('investorRevenueSharePercent');
        $investorCostShare = $data->percentRate('investorCostSharePercent');
        $investorCapexShare = $data->percentRate('investorCapexSharePercent');

        return RevenueSharingModel::profitSharing(
            investorRevenueShare: $investorRevenueShare,
            operatorRevenueShare: 1.0 - $investorRevenueShare,
            sharingBase: $data->string('sharingBase'),
            investorCostShare: $investorCostShare,
            operatorCostShare: 1.0 - $investorCostShare,
            investorCapexShare: $investorCapexShare,
            operatorCapexShare: 1.0 - $investorCapexShare,
        );
    }

    /**
     * @return array{capitalizable: float, immediate: float, iabEligibleCapitalizable: float}
     */
    private function brokerageAllocation(ScenarioFormData $data): array
    {
        $brokerageFee = $data->float('brokerageFee');

        return match($data->string('brokerageTreatment')) {
            'capitalize' => [
                'capitalizable' => $brokerageFee,
                'immediate' => 0.0,
                'iabEligibleCapitalizable' => $data->bool('brokerageIabEligible') ? $brokerageFee : 0.0,
            ],
            'immediate' => [
                'capitalizable' => 0.0,
                'immediate' => $brokerageFee,
                'iabEligibleCapitalizable' => 0.0,
            ],
            default => [
                'capitalizable' => 0.0,
                'immediate' => 0.0,
                'iabEligibleCapitalizable' => 0.0,
            ],
        };
    }
}
