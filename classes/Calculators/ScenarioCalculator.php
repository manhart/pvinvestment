<?php
declare(strict_types=1);

namespace pvinvestment\classes\Calculators;

use pvinvestment\classes\Domain\CalculationResult;
use pvinvestment\classes\Domain\ProjectTimingAssumptions;
use pvinvestment\classes\Domain\SavingsPlanAssumptions;
use pvinvestment\classes\Domain\Scenario\ScenarioComparison;
use pvinvestment\classes\Domain\Scenario\ScenarioInput;
use pvinvestment\classes\Domain\Scenario\ScenarioResult;
use pvinvestment\classes\Domain\TaxAssumptions;
use pvinvestment\classes\Domain\Tax\TaxLossLedger;

final class ScenarioCalculator
{
    public function __construct(
        private readonly AnnualInvestorCashflowCalculator $annualCalculator = new AnnualInvestorCashflowCalculator(),
    ) {}

    public function calculate(ScenarioInput $scenario): ScenarioResult
    {
        $annualResults = [];
        $cumulativeInvestorCashflow = 0.0;
        $cumulativeTaxCashflow = 0.0;
        $cumulativeInvestorBatteryRevenue = 0.0;
        $breakEvenYear = null;
        $runningSavingsCapital = $scenario->savingsPlanAssumptions->startingCapital;
        $taxLossLedger = new TaxLossLedger();

        for($offset = 0; $offset < $scenario->durationYears; $offset++) {
            $year = $scenario->taxAssumptions->calculationYear + $offset;
            $taxAssumptions = $this->taxAssumptionsForYear($scenario->taxAssumptions, $year);
            $timingAssumptions = $this->timingAssumptionsForYear(
                scenario: $scenario,
                year: $year,
            );
            $savingsPlanAssumptions = new SavingsPlanAssumptions(
                startingCapital: $runningSavingsCapital,
                monthlyContribution: $scenario->savingsPlanAssumptions->monthlyContribution,
                annualContribution: $scenario->savingsPlanAssumptions->annualContribution,
                positiveCashflowReinvestmentRate: $scenario->savingsPlanAssumptions->positiveCashflowReinvestmentRate,
            );

            $annualResult = $this->annualCalculator->calculate(
                pvAssumptions: $scenario->pvAssumptions,
                batteryModel: $scenario->batteryModel,
                financingAssumptions: $scenario->financingAssumptions,
                taxAssumptions: $taxAssumptions,
                savingsPlanAssumptions: $savingsPlanAssumptions,
                timingAssumptions: $timingAssumptions,
                taxLossLedger: $taxLossLedger,
            );
            $annualResults[] = $annualResult;

            $cumulativeInvestorCashflow += $annualResult->annualInvestorCashflow;
            $cumulativeTaxCashflow += $annualResult->annualTaxPayment;
            $cumulativeInvestorBatteryRevenue += $annualResult->investorBatteryRevenue;
            $runningSavingsCapital = $annualResult->savingsPlanEndingCapital;

            if($breakEvenYear === null && $cumulativeInvestorCashflow >= 0.0) {
                $breakEvenYear = $year;
            }
        }

        $totalInvestorResult = $cumulativeInvestorCashflow + $runningSavingsCapital;

        return new ScenarioResult(
            id: $scenario->id,
            description: $scenario->description,
            durationYears: $scenario->durationYears,
            annualResults: $annualResults,
            cumulativeInvestorCashflow: $cumulativeInvestorCashflow,
            cumulativeTaxCashflow: $cumulativeTaxCashflow,
            cumulativeInvestorBatteryRevenue: $cumulativeInvestorBatteryRevenue,
            savingsPlanEndingCapital: $runningSavingsCapital,
            totalInvestorResult: $totalInvestorResult,
            breakEvenYear: $breakEvenYear,
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

    private function taxAssumptionsForYear(TaxAssumptions $source, int $year): TaxAssumptions
    {
        return new TaxAssumptions(
            annualTaxPayment: $source->annualTaxPayment,
            calculationYear: $year,
            incomeTaxRate: $source->incomeTaxRate,
            acquisitionCost: $source->acquisitionCost,
            capitalizableAncillaryCosts: $source->capitalizableAncillaryCosts,
            immediatelyDeductibleCosts: $source->immediatelyDeductibleCosts,
            depreciationStartYear: $source->depreciationStartYear,
            depreciationStartMonth: $source->depreciationStartMonth,
            depreciationMethod: $source->depreciationMethod,
            linearDepreciationRate: $source->linearDepreciationRate,
            decliningDepreciationRate: $source->decliningDepreciationRate,
            iabEnabled: $source->iabEnabled,
            iabAmount: $source->iabAmount,
            iabDeductionYear: $source->iabDeductionYear,
            iabAdditionYear: $source->iabAdditionYear,
            specialDepreciationEnabled: $source->specialDepreciationEnabled,
            specialDepreciationRate: $source->specialDepreciationRate,
            specialDepreciationStartYear: $source->specialDepreciationStartYear,
            specialDepreciationYears: $source->specialDepreciationYears,
            taxPaymentDelayYears: $source->taxPaymentDelayYears,
            lossHandlingStrategy: $source->lossHandlingStrategy,
            maxLossCarryBackAmount: $source->maxLossCarryBackAmount,
            maxLossCarryBackYears: $source->maxLossCarryBackYears,
            manualUsableLossByYear: $source->manualUsableLossByYear,
            taxPaymentDelayMonths: $source->taxPaymentDelayMonths,
            taxRateByYear: $source->taxRateByYear,
        );
    }

    private function timingAssumptionsForYear(ScenarioInput $scenario, int $year): ProjectTimingAssumptions
    {
        $source = $scenario->timingAssumptions ?? ProjectTimingAssumptions::fromTaxAssumptions($scenario->taxAssumptions);

        return new ProjectTimingAssumptions(
            calculationYear: $year,
            investmentYear: $source->investmentYear,
            investmentMonth: $source->investmentMonth,
            depreciationStartYear: $source->depreciationStartYear,
            depreciationStartMonth: $source->depreciationStartMonth,
            eegCommissioningYear: $source->eegCommissioningYear,
            eegCommissioningMonth: $source->eegCommissioningMonth,
            gridConnectionYear: $source->gridConnectionYear,
            gridConnectionMonth: $source->gridConnectionMonth,
            revenueStartYear: $source->revenueStartYear,
            revenueStartMonth: $source->revenueStartMonth,
            interestStartYear: $source->interestStartYear,
            interestStartMonth: $source->interestStartMonth,
            repaymentStartYear: $source->repaymentStartYear,
            repaymentStartMonth: $source->repaymentStartMonth,
            taxPaymentYear: $year + $scenario->taxAssumptions->taxPaymentDelayYears,
            savingsPlanContributionStartYear: $source->savingsPlanContributionStartYear,
            savingsPlanContributionStartMonth: $source->savingsPlanContributionStartMonth,
            annualSavingsPlanContributionMonth: $source->annualSavingsPlanContributionMonth,
        );
    }
}
