<?php
declare(strict_types=1);

namespace pvinvestment\classes\Calculators;

use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\CalculationResult;
use pvinvestment\classes\Domain\FinancingAssumptions;
use pvinvestment\classes\Domain\ProjectTimingAssumptions;
use pvinvestment\classes\Domain\PvAssumptions;
use pvinvestment\classes\Domain\SavingsPlanAssumptions;
use pvinvestment\classes\Domain\TaxAssumptions;
use pvinvestment\classes\Domain\Tax\TaxLossLedger;

final class AnnualInvestorCashflowCalculator
{
    public function __construct(
        private readonly TaxCalculator $taxCalculator = new TaxCalculator(),
    ) {}

    public function calculate(
        PvAssumptions $pvAssumptions,
        BatteryModel $batteryModel,
        FinancingAssumptions $financingAssumptions,
        TaxAssumptions $taxAssumptions,
        SavingsPlanAssumptions $savingsPlanAssumptions,
        ?ProjectTimingAssumptions $timingAssumptions = null,
        ?TaxLossLedger $taxLossLedger = null,
    ): CalculationResult {
        $timingAssumptions ??= ProjectTimingAssumptions::fromTaxAssumptions($taxAssumptions);
        $revenueMonthFactor = $timingAssumptions->revenueMonthFactor();
        $interestMonthFactor = $timingAssumptions->interestMonthFactor();
        $repaymentMonthFactor = $timingAssumptions->repaymentMonthFactor();
        $savingsPlanContributionMonthFactor = $timingAssumptions->savingsPlanMonthlyContributionFactor();
        $batteryAllocation = $batteryModel->annualAllocation();
        $taxCalculation = $this->taxCalculator->calculate(
            taxAssumptions: $taxAssumptions,
            pvAssumptions: $pvAssumptions,
            batteryModel: $batteryModel,
            financingAssumptions: $financingAssumptions,
            timingAssumptions: $timingAssumptions,
            taxLossLedger: $taxLossLedger,
        );
        $pvRevenue = $pvAssumptions->revenueForMonthFactor($revenueMonthFactor);
        $pvOperatingCosts = $pvAssumptions->operatingCostsForMonthFactor($revenueMonthFactor);
        $investorBatteryRevenue = $batteryAllocation['investorRevenue'] * $revenueMonthFactor;
        $operatorBatteryRevenue = $batteryAllocation['operatorRevenue'] * $revenueMonthFactor;
        $investorBatteryCosts = $batteryAllocation['investorCosts'] * $revenueMonthFactor;
        $operatorBatteryCosts = $batteryAllocation['operatorCosts'] * $revenueMonthFactor;
        $batteryGrossRevenue = $batteryAllocation['grossRevenue'] * $revenueMonthFactor;
        $batteryNetRevenue = $batteryAllocation['netRevenue'] * $revenueMonthFactor;
        $batteryNetMargin = $batteryAllocation['netMargin'] * $revenueMonthFactor;
        $annualInterest = $financingAssumptions->interestForMonthFactor($interestMonthFactor);
        $annualRepayment = $financingAssumptions->repaymentForMonthFactor($repaymentMonthFactor);
        $annualInvestorCashflow =
            $pvRevenue
            - $pvOperatingCosts
            + $investorBatteryRevenue
            - $investorBatteryCosts
            - $annualInterest
            - $annualRepayment
            - $taxCalculation->cashflowTaxPayment;

        $fixedSavingsContribution = $savingsPlanAssumptions->fixedAnnualContribution(
            monthlyContributionMonthFactor: $savingsPlanContributionMonthFactor,
            includeAnnualContribution: $timingAssumptions->includesAnnualSavingsPlanContribution(),
        );
        $cashflowSavingsContribution = $savingsPlanAssumptions->annualContributionFromCashflow($annualInvestorCashflow);
        $savingsPlanEndingCapital =
            $savingsPlanAssumptions->startingCapital
            + $fixedSavingsContribution
            + $cashflowSavingsContribution;

        return new CalculationResult(
            pvRevenue: $pvRevenue,
            pvOperatingCosts: $pvOperatingCosts,
            investorBatteryRevenue: $investorBatteryRevenue,
            operatorBatteryRevenue: $operatorBatteryRevenue,
            investorBatteryCosts: $investorBatteryCosts,
            operatorBatteryCosts: $operatorBatteryCosts,
            batteryGrossRevenue: $batteryGrossRevenue,
            batteryNetRevenue: $batteryNetRevenue,
            batteryNetMargin: $batteryNetMargin,
            investorBatteryCapex: $batteryAllocation['investorCapex'],
            operatorBatteryCapex: $batteryAllocation['operatorCapex'],
            batterySharingBase: $batteryAllocation['sharingBase'],
            annualInterest: $annualInterest,
            annualRepayment: $annualRepayment,
            annualTaxPayment: $taxCalculation->cashflowTaxPayment,
            taxableIncome: $taxCalculation->taxableIncome,
            taxAmount: $taxCalculation->taxAmount,
            taxPaymentYear: $taxCalculation->taxPaymentYear,
            cashflowTaxPayment: $taxCalculation->cashflowTaxPayment,
            deductibleInterest: $taxCalculation->deductibleInterest,
            capitalizableAcquisitionCosts: $taxCalculation->capitalizableAcquisitionCosts,
            immediatelyDeductibleCosts: $taxCalculation->immediatelyDeductibleCosts,
            iabDeduction: $taxCalculation->iabDeduction,
            iabAddition: $taxCalculation->iabAddition,
            iabAcquisitionCostReduction: $taxCalculation->iabAcquisitionCostReduction,
            depreciationBasis: $taxCalculation->depreciationBasis,
            regularDepreciation: $taxCalculation->regularDepreciation,
            specialDepreciation: $taxCalculation->specialDepreciation,
            calculationYear: $timingAssumptions->calculationYear,
            investmentYear: $timingAssumptions->investmentYear,
            investmentMonth: $timingAssumptions->investmentMonth,
            depreciationStartYear: $timingAssumptions->depreciationStartYear,
            depreciationStartMonth: $timingAssumptions->depreciationStartMonth,
            eegCommissioningYear: $timingAssumptions->eegCommissioningYear,
            eegCommissioningMonth: $timingAssumptions->eegCommissioningMonth,
            gridConnectionYear: $timingAssumptions->gridConnectionYear,
            gridConnectionMonth: $timingAssumptions->gridConnectionMonth,
            revenueStartYear: $timingAssumptions->revenueStartYear,
            revenueStartMonth: $timingAssumptions->revenueStartMonth,
            interestStartYear: $timingAssumptions->interestStartYear,
            interestStartMonth: $timingAssumptions->interestStartMonth,
            repaymentStartYear: $timingAssumptions->repaymentStartYear,
            repaymentStartMonth: $timingAssumptions->repaymentStartMonth,
            savingsPlanContributionStartYear: $timingAssumptions->savingsPlanContributionStartYear,
            savingsPlanContributionStartMonth: $timingAssumptions->savingsPlanContributionStartMonth,
            revenueMonthFactor: $revenueMonthFactor,
            interestMonthFactor: $interestMonthFactor,
            repaymentMonthFactor: $repaymentMonthFactor,
            savingsPlanContributionMonthFactor: $savingsPlanContributionMonthFactor,
            annualInvestorCashflow: $annualInvestorCashflow,
            savingsPlanStartingCapital: $savingsPlanAssumptions->startingCapital,
            savingsPlanFixedContribution: $fixedSavingsContribution,
            savingsPlanCashflowContribution: $cashflowSavingsContribution,
            savingsPlanEndingCapital: $savingsPlanEndingCapital,
        );
    }
}
