<?php
declare(strict_types=1);

namespace pvinvestment\classes\Calculators;

use pvinvestment\classes\Domain\ProjectTimingAssumptions;
use pvinvestment\classes\Domain\Results\MonthResult;
use pvinvestment\classes\Domain\Scenario\ScenarioInput;
use pvinvestment\classes\Domain\TaxAssumptions;
use pvinvestment\classes\Domain\Tax\TaxLossLedger;
use pvinvestment\classes\Domain\Time\YearMonth;

final class MonthlyScenarioCalculator
{
    public function __construct(
        private readonly TaxCalculator $taxCalculator = new TaxCalculator(),
    ) {}

    /**
     * @return list<MonthResult>
     */
    public function calculate(ScenarioInput $scenario): array
    {
        $baseYear = $scenario->taxAssumptions->calculationYear;
        $endYear = $baseYear + $scenario->durationYears - 1;
        $sourceTiming = $scenario->timingAssumptions ?? ProjectTimingAssumptions::fromTaxAssumptions($scenario->taxAssumptions);

        $taxByPaymentMonth = $this->taxCashflowsByPaymentMonth($scenario, $sourceTiming, $baseYear, $endYear);
        $depreciationByMonth = $this->depreciationByMonth($scenario, $sourceTiming, $baseYear, $endYear);
        $batteryAllocation = $scenario->batteryModel->annualAllocation();
        $revenueStart = new YearMonth($sourceTiming->revenueStartYear, $sourceTiming->revenueStartMonth);
        $interestStart = new YearMonth($sourceTiming->interestStartYear, $sourceTiming->interestStartMonth);
        $repaymentStart = new YearMonth($sourceTiming->repaymentStartYear, $sourceTiming->repaymentStartMonth);
        $savingsStart = new YearMonth($sourceTiming->savingsPlanContributionStartYear, $sourceTiming->savingsPlanContributionStartMonth);

        $months = [];
        $savingsValue = $scenario->savingsPlanAssumptions->startingCapital;
        for($year = $baseYear; $year <= $endYear; $year++) {
            for($month = 1; $month <= 12; $month++) {
                $current = new YearMonth($year, $month);
                $hasRevenue = $current->isOnOrAfter($revenueStart);
                $hasInterest = $current->isOnOrAfter($interestStart);
                $hasRepayment = $current->isOnOrAfter($repaymentStart);

                $pvRevenue = $hasRevenue ? $scenario->pvAssumptions->annualRevenue / 12.0 : 0.0;
                $operatingCosts = $hasRevenue ? $scenario->pvAssumptions->annualOperatingCosts / 12.0 : 0.0;
                $batteryGrossRevenue = $hasRevenue ? $batteryAllocation['grossRevenue'] / 12.0 : 0.0;
                $batteryInvestorRevenue = $hasRevenue ? $batteryAllocation['investorRevenue'] / 12.0 : 0.0;
                $batteryInvestorCosts = $hasRevenue ? $batteryAllocation['investorCosts'] / 12.0 : 0.0;
                $financingInterest = $hasInterest ? $scenario->financingAssumptions->annualInterest / 12.0 : 0.0;
                $financingPrincipal = $hasRepayment ? $scenario->financingAssumptions->annualRepayment / 12.0 : 0.0;
                $depreciation = $depreciationByMonth[$this->monthKey($year, $month)] ?? 0.0;
                $taxCashflow = $taxByPaymentMonth[$this->monthKey($year, $month)] ?? 0.0;
                $taxableResultComponent = $pvRevenue
                    + $batteryInvestorRevenue
                    - $batteryInvestorCosts
                    - $operatingCosts
                    - $financingInterest
                    - $depreciation;
                $investorCashflowBeforeSavings = $pvRevenue
                    + $batteryInvestorRevenue
                    - $batteryInvestorCosts
                    - $operatingCosts
                    - $financingInterest
                    - $financingPrincipal
                    - $taxCashflow;
                $savingsContribution = $this->savingsContribution($scenario, $sourceTiming, $current, $savingsStart, $investorCashflowBeforeSavings);
                $savingsReturn = 0.0;
                $savingsTax = 0.0;
                $savingsValue += $savingsContribution + $savingsReturn - $savingsTax;
                $freeCashflowAfterSavings = $investorCashflowBeforeSavings - $savingsContribution;

                $months[] = new MonthResult(
                    year: $year,
                    month: $month,
                    pvRevenue: $pvRevenue,
                    batteryGrossRevenue: $batteryGrossRevenue,
                    batteryInvestorRevenue: $batteryInvestorRevenue,
                    batteryInvestorCosts: $batteryInvestorCosts,
                    operatingCosts: $operatingCosts,
                    financingInterest: $financingInterest,
                    financingPrincipal: $financingPrincipal,
                    depreciation: $depreciation,
                    taxableResultComponent: $taxableResultComponent,
                    taxCashflow: $taxCashflow,
                    investorCashflowBeforeSavings: $investorCashflowBeforeSavings,
                    savingsContribution: $savingsContribution,
                    savingsReturn: $savingsReturn,
                    savingsTax: $savingsTax,
                    savingsEndValue: $savingsValue,
                    freeCashflowAfterSavings: $freeCashflowAfterSavings,
                );
            }
        }

        return $months;
    }

    /**
     * @return array<string, float>
     */
    private function taxCashflowsByPaymentMonth(ScenarioInput $scenario, ProjectTimingAssumptions $sourceTiming, int $baseYear, int $endYear): array
    {
        $taxCashflows = [];
        $taxLossLedger = new TaxLossLedger();
        for($year = $baseYear; $year <= $endYear; $year++) {
            $taxAssumptions = $this->taxAssumptionsForYear($scenario->taxAssumptions, $year);
            $timingAssumptions = $this->timingAssumptionsForYear($scenario, $sourceTiming, $year);
            $taxCalculation = $this->taxCalculator->calculate(
                taxAssumptions: $taxAssumptions,
                pvAssumptions: $scenario->pvAssumptions,
                batteryModel: $scenario->batteryModel,
                financingAssumptions: $scenario->financingAssumptions,
                timingAssumptions: $timingAssumptions,
                taxLossLedger: $taxLossLedger,
            );
            $paymentYear = $taxCalculation->taxCashflowYear;
            if($paymentYear >= $baseYear && $paymentYear <= $endYear) {
                $key = $this->monthKey($paymentYear, $taxCalculation->taxCashflowMonth);
                $taxCashflows[$key] = ($taxCashflows[$key] ?? 0.0) + $taxCalculation->taxAmount;
            }
        }

        return $taxCashflows;
    }

    /**
     * @return array<string, float>
     */
    private function depreciationByMonth(ScenarioInput $scenario, ProjectTimingAssumptions $sourceTiming, int $baseYear, int $endYear): array
    {
        $depreciationByMonth = [];
        for($year = $baseYear; $year <= $endYear; $year++) {
            $taxAssumptions = $this->taxAssumptionsForYear($scenario->taxAssumptions, $year);
            $timingAssumptions = $this->timingAssumptionsForYear($scenario, $sourceTiming, $year);
            $taxCalculation = $this->taxCalculator->calculate(
                taxAssumptions: $taxAssumptions,
                pvAssumptions: $scenario->pvAssumptions,
                batteryModel: $scenario->batteryModel,
                financingAssumptions: $scenario->financingAssumptions,
                timingAssumptions: $timingAssumptions,
            );
            $annualDepreciation = $taxCalculation->totalDepreciation();
            $depreciationMonths = $this->activeMonthsInYear(
                calculationYear: $year,
                startYear: $sourceTiming->depreciationStartYear,
                startMonth: $sourceTiming->depreciationStartMonth,
            );
            if($annualDepreciation === 0.0 || $depreciationMonths === 0) {
                continue;
            }
            $monthlyDepreciation = $annualDepreciation / $depreciationMonths;
            $startMonth = $year === $sourceTiming->depreciationStartYear ? $sourceTiming->depreciationStartMonth : 1;
            for($month = $startMonth; $month <= 12; $month++) {
                $depreciationByMonth[$this->monthKey($year, $month)] = $monthlyDepreciation;
            }
        }

        return $depreciationByMonth;
    }

    private function savingsContribution(
        ScenarioInput $scenario,
        ProjectTimingAssumptions $sourceTiming,
        YearMonth $current,
        YearMonth $savingsStart,
        float $investorCashflowBeforeSavings,
    ): float {
        if(!$current->isOnOrAfter($savingsStart)) {
            return 0.0;
        }

        $contribution = $scenario->savingsPlanAssumptions->monthlyContribution;
        if($current->month === $sourceTiming->annualSavingsPlanContributionMonth) {
            $contribution += $scenario->savingsPlanAssumptions->annualContribution;
        }

        return $contribution
            + (max(0.0, $investorCashflowBeforeSavings) * $scenario->savingsPlanAssumptions->positiveCashflowReinvestmentRate);
    }

    private function activeMonthsInYear(int $calculationYear, int $startYear, int $startMonth): int
    {
        if($calculationYear < $startYear) {
            return 0;
        }
        if($calculationYear === $startYear) {
            return 13 - $startMonth;
        }

        return 12;
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

    private function timingAssumptionsForYear(ScenarioInput $scenario, ProjectTimingAssumptions $source, int $year): ProjectTimingAssumptions
    {
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

    private function monthKey(int $year, int $month): string
    {
        return sprintf('%04d-%02d', $year, $month);
    }
}
