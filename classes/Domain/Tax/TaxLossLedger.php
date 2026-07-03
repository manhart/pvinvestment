<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain\Tax;

use pvinvestment\classes\Domain\MoneyAmount;
use pvinvestment\classes\Domain\PercentageRate;
use pvinvestment\classes\Domain\TaxAssumptions;

final class TaxLossLedger
{
    /**
     * @param array<int, float> $priorPositiveTaxableCapacityByYear
     */
    public function __construct(
        private float $lossCarriedForward = 0.0,
        private array $priorPositiveTaxableCapacityByYear = [],
    ) {}

    public function apply(
        TaxAssumptions $taxAssumptions,
        float $taxableResultBeforeLoss,
        int $taxCashflowYear,
        int $taxCashflowMonth,
    ): TaxYearResult {
        $year = $taxAssumptions->calculationYear;
        $taxRate = $taxAssumptions->taxRateForYear($year);
        $lossUsed = 0.0;
        $lossCreated = 0.0;
        $taxableResultAfterLoss = $taxableResultBeforeLoss;

        switch($taxAssumptions->lossHandlingStrategy) {
            case TaxLossHandlingStrategy::IMMEDIATE:
                if($taxableResultBeforeLoss < 0.0) {
                    $lossUsed = abs($taxableResultBeforeLoss);
                }
                break;

            case TaxLossHandlingStrategy::CARRY_FORWARD:
                if($taxableResultBeforeLoss < 0.0) {
                    $lossCreated = abs($taxableResultBeforeLoss);
                    $this->lossCarriedForward += $lossCreated;
                    $taxableResultAfterLoss = 0.0;
                } elseif($this->lossCarriedForward > 0.0) {
                    $lossUsed = min($taxableResultBeforeLoss, $this->lossCarriedForward);
                    $this->lossCarriedForward -= $lossUsed;
                    $taxableResultAfterLoss = $taxableResultBeforeLoss - $lossUsed;
                }
                break;

            case TaxLossHandlingStrategy::CARRY_BACK:
                if($taxableResultBeforeLoss < 0.0) {
                    $lossUsed = $this->usableCarryBackLoss($taxAssumptions, abs($taxableResultBeforeLoss));
                    $lossCreated = max(0.0, abs($taxableResultBeforeLoss) - $lossUsed);
                    $taxableResultAfterLoss = -$lossUsed;
                }
                break;

            case TaxLossHandlingStrategy::MANUAL:
                $manualUsableLoss = $taxAssumptions->manualUsableLossByYear[$year] ?? 0.0;
                if($taxableResultBeforeLoss < 0.0) {
                    $lossUsed = min(abs($taxableResultBeforeLoss), $manualUsableLoss);
                    $lossCreated = max(0.0, abs($taxableResultBeforeLoss) - $lossUsed);
                    $taxableResultAfterLoss = -$lossUsed;
                } elseif($manualUsableLoss > 0.0) {
                    $lossUsed = min($taxableResultBeforeLoss, $manualUsableLoss);
                    $taxableResultAfterLoss = $taxableResultBeforeLoss - $lossUsed;
                }
                break;

            case TaxLossHandlingStrategy::NONE:
                if($taxableResultBeforeLoss < 0.0) {
                    $lossCreated = abs($taxableResultBeforeLoss);
                    $taxableResultAfterLoss = 0.0;
                }
                break;
        }

        if($taxableResultAfterLoss > 0.0) {
            $this->priorPositiveTaxableCapacityByYear[$year] = ($this->priorPositiveTaxableCapacityByYear[$year] ?? 0.0) + $taxableResultAfterLoss;
        }

        $taxAmount = MoneyAmount::fromEuro($taxableResultAfterLoss)
            ->multiplyRate(PercentageRate::fromDecimal($taxRate))
            ->toEuro();

        return new TaxYearResult(
            year: $year,
            taxableResultBeforeLoss: $taxableResultBeforeLoss,
            lossUsed: $lossUsed,
            lossCreated: $lossCreated,
            lossCarriedForward: $this->lossCarriedForward,
            taxableResultAfterLoss: $taxableResultAfterLoss,
            taxAmount: $taxAmount,
            taxCashflowYear: $taxCashflowYear,
            taxCashflowMonth: $taxCashflowMonth,
        );
    }

    private function usableCarryBackLoss(TaxAssumptions $taxAssumptions, float $lossAmount): float
    {
        $remaining = min($lossAmount, $taxAssumptions->maxLossCarryBackAmount);
        if($remaining <= 0.0 || $taxAssumptions->maxLossCarryBackYears < 1) {
            return 0.0;
        }

        $used = 0.0;
        for($year = $taxAssumptions->calculationYear - 1; $year >= $taxAssumptions->calculationYear - $taxAssumptions->maxLossCarryBackYears; $year--) {
            $capacity = $this->priorPositiveTaxableCapacityByYear[$year] ?? 0.0;
            if($capacity <= 0.0) {
                continue;
            }
            $yearUsage = min($capacity, $remaining);
            $this->priorPositiveTaxableCapacityByYear[$year] -= $yearUsage;
            $used += $yearUsage;
            $remaining -= $yearUsage;
            if($remaining <= 0.0) {
                break;
            }
        }

        return $used;
    }
}
