<?php
declare(strict_types=1);

namespace pvinvestment\classes\Calculators;

use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\FinancingAssumptions;
use pvinvestment\classes\Domain\ProjectTimingAssumptions;
use pvinvestment\classes\Domain\PvAssumptions;
use pvinvestment\classes\Domain\TaxAssumptions;
use pvinvestment\classes\Domain\TaxCalculationResult;
use pvinvestment\classes\Domain\Tax\TaxLossLedger;
use pvinvestment\classes\Domain\Tax\TaxAsset;
use pvinvestment\classes\Domain\Tax\TaxAssetLedger;

final class TaxCalculator
{
    public function __construct(
        private readonly DepreciationCalculator $depreciationCalculator = new DepreciationCalculator(),
    ) {}

    public function calculate(
        TaxAssumptions $taxAssumptions,
        PvAssumptions $pvAssumptions,
        BatteryModel $batteryModel,
        FinancingAssumptions $financingAssumptions,
        ?ProjectTimingAssumptions $timingAssumptions = null,
        ?TaxAssetLedger $taxAssetLedger = null,
        ?TaxLossLedger $taxLossLedger = null,
    ): TaxCalculationResult {
        $timingAssumptions ??= ProjectTimingAssumptions::fromTaxAssumptions($taxAssumptions);
        $revenueMonthFactor = $timingAssumptions->revenueMonthFactor();
        $deductibleInterest = $financingAssumptions->interestForMonthFactor($timingAssumptions->interestMonthFactor());
        [$taxCashflowYear, $taxCashflowMonth] = $this->taxCashflowDate($taxAssumptions, $timingAssumptions);

        if(!$taxAssumptions->usesComputedTaxModel()) {
            return new TaxCalculationResult(
                taxableRevenue: 0.0,
                deductibleOperatingCosts: 0.0,
                deductibleInterest: $deductibleInterest,
                immediatelyDeductibleCosts: 0.0,
                capitalizableAcquisitionCosts: 0.0,
                iabDeduction: 0.0,
                iabAddition: 0.0,
                iabAcquisitionCostReduction: 0.0,
                depreciationBasis: 0.0,
                regularDepreciation: 0.0,
                specialDepreciation: 0.0,
                taxableIncome: 0.0,
                taxAmount: $taxAssumptions->annualTaxPayment,
                taxPaymentYear: $timingAssumptions->taxPaymentYear,
                cashflowTaxPayment: $timingAssumptions->taxPaymentYear === $timingAssumptions->calculationYear ? $taxAssumptions->annualTaxPayment : 0.0,
                taxableResultBeforeLoss: 0.0,
                taxableResultAfterLoss: 0.0,
                taxCashflowYear: $taxCashflowYear,
                taxCashflowMonth: $taxCashflowMonth,
                iabEligibleInvestmentBasis: 0.0,
            );
        }

        $batteryAllocation = $batteryModel->annualAllocation();
        $taxableRevenue = $pvAssumptions->revenueForMonthFactor($revenueMonthFactor) + ($batteryAllocation['investorRevenue'] * $revenueMonthFactor);
        $deductibleOperatingCosts = $pvAssumptions->operatingCostsForMonthFactor($revenueMonthFactor) + ($batteryAllocation['investorCosts'] * $revenueMonthFactor);
        $iabEligibleInvestmentBasis = $taxAssumptions->iabEligibleInvestmentBasis();
        $iabDeduction = $this->iabDeduction($taxAssumptions);
        $iabAddition = $this->iabAddition($taxAssumptions);
        $iabAcquisitionCostReduction = $this->iabAcquisitionCostReduction($taxAssumptions);
        $taxAssetLedger ??= $this->legacyTaxAssetLedger($taxAssumptions, $timingAssumptions, $iabAcquisitionCostReduction);
        $capitalizableAcquisitionCosts = $taxAssetLedger?->capitalizableAcquisitionCosts()
            ?? ($taxAssumptions->acquisitionCost + $taxAssumptions->capitalizableAncillaryCosts);
        $depreciationBasis = $taxAssetLedger?->depreciationBasis() ?? max(0.0, $capitalizableAcquisitionCosts - $iabAcquisitionCostReduction);
        $depreciationYear = $taxAssetLedger?->depreciationForYear(
            year: $timingAssumptions->calculationYear,
            calculator: $this->depreciationCalculator,
        );
        $regularDepreciation = $depreciationYear?->regularDepreciation ?? 0.0;
        $specialDepreciation = $depreciationYear?->specialDepreciation ?? 0.0;

        $taxableResultBeforeLoss =
            $taxableRevenue
            - $deductibleOperatingCosts
            - $deductibleInterest
            - $taxAssumptions->immediatelyDeductibleCosts
            - $regularDepreciation
            - $specialDepreciation
            - $iabDeduction
            + $iabAddition;

        $taxLossLedger ??= new TaxLossLedger();
        $taxYearResult = $taxLossLedger->apply(
            taxAssumptions: $taxAssumptions,
            taxableResultBeforeLoss: $taxableResultBeforeLoss,
            taxCashflowYear: $taxCashflowYear,
            taxCashflowMonth: $taxCashflowMonth,
        );
        $taxableIncome = $taxYearResult->taxableResultAfterLoss;
        $taxAmount = $taxYearResult->taxAmount;
        $taxPaymentYear = $timingAssumptions->taxPaymentYear;
        $cashflowTaxPayment = $taxYearResult->taxCashflowYear === $timingAssumptions->calculationYear ? $taxAmount : 0.0;

        return new TaxCalculationResult(
            taxableRevenue: $taxableRevenue,
            deductibleOperatingCosts: $deductibleOperatingCosts,
            deductibleInterest: $deductibleInterest,
            immediatelyDeductibleCosts: $taxAssumptions->immediatelyDeductibleCosts,
            capitalizableAcquisitionCosts: $capitalizableAcquisitionCosts,
            iabDeduction: $iabDeduction,
            iabAddition: $iabAddition,
            iabAcquisitionCostReduction: $iabAcquisitionCostReduction,
            depreciationBasis: $depreciationBasis,
            regularDepreciation: $regularDepreciation,
            specialDepreciation: $specialDepreciation,
            taxableIncome: $taxableIncome,
            taxAmount: $taxAmount,
            taxPaymentYear: $taxPaymentYear,
            cashflowTaxPayment: $cashflowTaxPayment,
            taxableResultBeforeLoss: $taxYearResult->taxableResultBeforeLoss,
            lossUsed: $taxYearResult->lossUsed,
            lossCreated: $taxYearResult->lossCreated,
            lossCarriedForward: $taxYearResult->lossCarriedForward,
            taxableResultAfterLoss: $taxYearResult->taxableResultAfterLoss,
            taxCashflowYear: $taxYearResult->taxCashflowYear,
            taxCashflowMonth: $taxYearResult->taxCashflowMonth,
            iabEligibleInvestmentBasis: $iabEligibleInvestmentBasis,
        );
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function taxCashflowDate(TaxAssumptions $taxAssumptions, ProjectTimingAssumptions $timingAssumptions): array
    {
        $year = $timingAssumptions->taxPaymentYear;
        $month = 12 + $taxAssumptions->taxPaymentDelayMonths;
        while($month > 12) {
            $year++;
            $month -= 12;
        }

        return [$year, $month];
    }

    private function legacyTaxAssetLedger(TaxAssumptions $taxAssumptions, ProjectTimingAssumptions $timingAssumptions, float $iabAcquisitionCostReduction): ?TaxAssetLedger
    {
        $annualDepreciationRate = $taxAssumptions->depreciationMethod === TaxAssumptions::DEPRECIATION_DECLINING
            ? $taxAssumptions->decliningDepreciationRate
            : $taxAssumptions->linearDepreciationRate;
        if($annualDepreciationRate <= 0.0 && !$taxAssumptions->specialDepreciationEnabled) {
            return null;
        }

        $usefulLifeMonths = $annualDepreciationRate > 0.0
            ? max(1, (int)round(12.0 / $annualDepreciationRate))
            : 12;
        $depreciationMethod = $taxAssumptions->depreciationMethod === TaxAssumptions::DEPRECIATION_DECLINING
            ? TaxAsset::DEPRECIATION_DECLINING_BALANCE
            : TaxAsset::DEPRECIATION_LINEAR;
        $specialDepreciationDistribution = [];
        if($taxAssumptions->specialDepreciationEnabled) {
            $lastSpecialYear = $taxAssumptions->specialDepreciationStartYear + $taxAssumptions->specialDepreciationYears - 1;
            for($year = $taxAssumptions->specialDepreciationStartYear; $year <= $lastSpecialYear; $year++) {
                $specialDepreciationDistribution[$year] = $taxAssumptions->specialDepreciationRate;
            }
        }

        return new TaxAssetLedger([
            new TaxAsset(
                assetName: 'PV-Anlage',
                acquisitionCost: $taxAssumptions->acquisitionCost,
                capitalizableAncillaryCosts: $taxAssumptions->capitalizableAncillaryCosts,
                acquisitionDate: new \DateTimeImmutable(sprintf('%04d-%02d-01', $timingAssumptions->investmentYear, $timingAssumptions->investmentMonth)),
                depreciationStart: new \DateTimeImmutable(sprintf('%04d-%02d-01', $timingAssumptions->depreciationStartYear, $timingAssumptions->depreciationStartMonth)),
                usefulLifeMonths: $usefulLifeMonths,
                depreciationMethod: $depreciationMethod,
                decliningBalanceRate: $taxAssumptions->decliningDepreciationRate,
                switchToLinear: TaxAsset::SWITCH_TO_LINEAR_NO,
                iabReductionAmount: $iabAcquisitionCostReduction,
                specialDepreciationEnabled: $taxAssumptions->specialDepreciationEnabled,
                specialDepreciationTotalRate: $taxAssumptions->specialDepreciationRate * $taxAssumptions->specialDepreciationYears,
                specialDepreciationDistributionByYear: $specialDepreciationDistribution,
            ),
        ]);
    }

    private function iabDeduction(TaxAssumptions $taxAssumptions): float
    {
        if(!$taxAssumptions->iabEnabled || $taxAssumptions->calculationYear !== $taxAssumptions->iabDeductionYear) {
            return 0.0;
        }
        return $taxAssumptions->effectiveIabAmount();
    }

    private function iabAddition(TaxAssumptions $taxAssumptions): float
    {
        if(!$taxAssumptions->iabEnabled || $taxAssumptions->calculationYear !== $taxAssumptions->iabAdditionYear) {
            return 0.0;
        }
        return $taxAssumptions->effectiveIabAmount();
    }

    private function iabAcquisitionCostReduction(TaxAssumptions $taxAssumptions): float
    {
        if(!$taxAssumptions->iabEnabled || $taxAssumptions->calculationYear < $taxAssumptions->iabAdditionYear) {
            return 0.0;
        }
        return min(
            $taxAssumptions->effectiveIabAmount(),
            $taxAssumptions->acquisitionCost + $taxAssumptions->capitalizableAncillaryCosts,
        );
    }
}
