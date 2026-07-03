<?php
declare(strict_types=1);

namespace pvinvestment\classes\Calculators;

use InvalidArgumentException;
use pvinvestment\classes\Domain\Tax\DepreciationSchedule;
use pvinvestment\classes\Domain\Tax\DepreciationYear;
use pvinvestment\classes\Domain\Tax\TaxAsset;

final class DepreciationCalculator
{
    public function calculate(TaxAsset $asset, int $startYear, int $endYear): DepreciationSchedule
    {
        if($endYear < $startYear) {
            throw new InvalidArgumentException('Depreciation end year must not be before start year.');
        }

        $scheduleYears = [];
        $openingBookValue = $asset->depreciationBasis();
        $switchedToLinear = false;

        for($year = min($startYear, $asset->depreciationStartYear()); $year <= $endYear; $year++) {
            if($year < $asset->depreciationStartYear()) {
                if($year >= $startYear) {
                    $scheduleYears[] = new DepreciationYear(
                        year: $year,
                        assetName: $asset->assetName,
                        openingBookValue: 0.0,
                        regularDepreciation: 0.0,
                        specialDepreciation: 0.0,
                        closingBookValue: 0.0,
                        depreciationMonths: 0,
                        methodUsed: 'none',
                    );
                }
                continue;
            }

            $depreciationMonths = $this->depreciationMonthsInYear($asset, $year);
            $remainingMonths = max(0, $asset->usefulLifeMonths - $this->elapsedDepreciationMonthsBeforeYear($asset, $year));
            $methodUsed = $depreciationMonths > 0 ? $asset->depreciationMethod : 'none';
            $regularDepreciation = 0.0;

            if($depreciationMonths > 0 && $openingBookValue > $asset->residualBookValue) {
                $linearDepreciation = $this->linearDepreciation($asset, $openingBookValue, $remainingMonths, $depreciationMonths);
                if($asset->depreciationMethod === TaxAsset::DEPRECIATION_DECLINING_BALANCE && !$switchedToLinear) {
                    $decliningDepreciation = $this->decliningDepreciation($asset, $openingBookValue, $depreciationMonths);
                    if($this->shouldSwitchToLinear($asset, $linearDepreciation, $decliningDepreciation)) {
                        $switchedToLinear = true;
                        $regularDepreciation = $linearDepreciation;
                        $methodUsed = TaxAsset::DEPRECIATION_LINEAR;
                    } else {
                        $regularDepreciation = $decliningDepreciation;
                        $methodUsed = TaxAsset::DEPRECIATION_DECLINING_BALANCE;
                    }
                } else {
                    $regularDepreciation = $linearDepreciation;
                    $methodUsed = TaxAsset::DEPRECIATION_LINEAR;
                }
                $regularDepreciation = min($regularDepreciation, max(0.0, $openingBookValue - $asset->residualBookValue));
            }

            $specialDepreciation = $this->specialDepreciation($asset, $year, $openingBookValue, $regularDepreciation);
            $closingBookValue = max(
                $asset->residualBookValue,
                $openingBookValue - $regularDepreciation - $specialDepreciation,
            );

            if($year >= $startYear) {
                $scheduleYears[] = new DepreciationYear(
                    year: $year,
                    assetName: $asset->assetName,
                    openingBookValue: $openingBookValue,
                    regularDepreciation: $regularDepreciation,
                    specialDepreciation: $specialDepreciation,
                    closingBookValue: $closingBookValue,
                    depreciationMonths: $depreciationMonths,
                    methodUsed: $methodUsed,
                );
            }

            $openingBookValue = $closingBookValue;
        }

        return new DepreciationSchedule($asset, $scheduleYears);
    }

    private function linearDepreciation(TaxAsset $asset, float $openingBookValue, int $remainingMonths, int $depreciationMonths): float
    {
        if($remainingMonths <= 0) {
            return 0.0;
        }

        return (($openingBookValue - $asset->residualBookValue) / $remainingMonths) * $depreciationMonths;
    }

    private function decliningDepreciation(TaxAsset $asset, float $openingBookValue, int $depreciationMonths): float
    {
        return ($openingBookValue - $asset->residualBookValue) * $asset->decliningBalanceRate * ($depreciationMonths / 12.0);
    }

    private function shouldSwitchToLinear(TaxAsset $asset, float $linearDepreciation, float $decliningDepreciation): bool
    {
        if(!in_array($asset->switchToLinear, [TaxAsset::SWITCH_TO_LINEAR_AUTO, TaxAsset::SWITCH_TO_LINEAR_YES], true)) {
            return false;
        }

        return $linearDepreciation > $decliningDepreciation;
    }

    private function specialDepreciation(TaxAsset $asset, int $year, float $openingBookValue, float $regularDepreciation): float
    {
        if(!$asset->specialDepreciationEnabled) {
            return 0.0;
        }

        $rate = $asset->specialDepreciationDistributionByYear[$year] ?? 0.0;
        $candidate = $asset->depreciationBasis() * $rate;
        $maximum = max(0.0, $openingBookValue - $regularDepreciation - $asset->residualBookValue);

        return min($candidate, $maximum);
    }

    private function depreciationMonthsInYear(TaxAsset $asset, int $year): int
    {
        $remainingMonths = $asset->usefulLifeMonths - $this->elapsedDepreciationMonthsBeforeYear($asset, $year);
        if($remainingMonths <= 0) {
            return 0;
        }
        if($year < $asset->depreciationStartYear()) {
            return 0;
        }
        if($year === $asset->depreciationStartYear()) {
            return min(13 - $asset->depreciationStartMonth(), $remainingMonths);
        }

        return min(12, $remainingMonths);
    }

    private function elapsedDepreciationMonthsBeforeYear(TaxAsset $asset, int $year): int
    {
        if($year <= $asset->depreciationStartYear()) {
            return 0;
        }

        return min(
            $asset->usefulLifeMonths,
            (13 - $asset->depreciationStartMonth()) + (($year - $asset->depreciationStartYear() - 1) * 12),
        );
    }
}
