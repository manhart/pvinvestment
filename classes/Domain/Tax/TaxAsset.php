<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain\Tax;

use DateTimeImmutable;
use InvalidArgumentException;

final class TaxAsset
{
    public const DEPRECIATION_LINEAR = 'linear';
    public const DEPRECIATION_DECLINING_BALANCE = 'declining_balance';

    public const SWITCH_TO_LINEAR_YES = 'yes';
    public const SWITCH_TO_LINEAR_NO = 'no';
    public const SWITCH_TO_LINEAR_AUTO = 'auto';
    public const SWITCH_TO_LINEAR_MANUAL = 'manual';

    /**
     * @param array<int, float> $specialDepreciationDistributionByYear decimal rates by calendar year
     */
    public function __construct(
        public readonly string $assetName,
        public readonly float $acquisitionCost,
        public readonly float $capitalizableAncillaryCosts,
        public readonly DateTimeImmutable $acquisitionDate,
        public readonly DateTimeImmutable $depreciationStart,
        public readonly int $usefulLifeMonths,
        public readonly string $depreciationMethod = self::DEPRECIATION_LINEAR,
        public readonly float $decliningBalanceRate = 0.0,
        public readonly string $switchToLinear = self::SWITCH_TO_LINEAR_AUTO,
        public readonly float $iabReductionAmount = 0.0,
        public readonly bool $specialDepreciationEnabled = false,
        public readonly float $specialDepreciationTotalRate = 0.0,
        public readonly array $specialDepreciationDistributionByYear = [],
        public readonly float $residualBookValue = 0.0,
    ) {
        if($assetName === '') {
            throw new InvalidArgumentException('Asset name must not be empty.');
        }
        foreach([
            'acquisitionCost' => $acquisitionCost,
            'capitalizableAncillaryCosts' => $capitalizableAncillaryCosts,
            'decliningBalanceRate' => $decliningBalanceRate,
            'iabReductionAmount' => $iabReductionAmount,
            'specialDepreciationTotalRate' => $specialDepreciationTotalRate,
            'residualBookValue' => $residualBookValue,
        ] as $label => $value) {
            if($value < 0.0) {
                throw new InvalidArgumentException($label.' must not be negative.');
            }
        }
        if($usefulLifeMonths < 1) {
            throw new InvalidArgumentException('Useful life must be at least one month.');
        }
        if(!in_array($depreciationMethod, [self::DEPRECIATION_LINEAR, self::DEPRECIATION_DECLINING_BALANCE], true)) {
            throw new InvalidArgumentException('Unsupported depreciation method.');
        }
        if(!in_array($switchToLinear, [
            self::SWITCH_TO_LINEAR_YES,
            self::SWITCH_TO_LINEAR_NO,
            self::SWITCH_TO_LINEAR_AUTO,
            self::SWITCH_TO_LINEAR_MANUAL,
        ], true)) {
            throw new InvalidArgumentException('Unsupported switch-to-linear mode.');
        }
        if($specialDepreciationTotalRate > 1.0) {
            throw new InvalidArgumentException('Special depreciation total rate must not exceed 1.');
        }

        $distributedRate = 0.0;
        foreach($specialDepreciationDistributionByYear as $year => $rate) {
            if(!is_int($year)) {
                throw new InvalidArgumentException('Special depreciation distribution years must be integer calendar years.');
            }
            if($rate < 0.0 || $rate > 1.0) {
                throw new InvalidArgumentException('Special depreciation distribution rates must be between 0 and 1.');
            }
            $distributedRate += $rate;
        }
        if($distributedRate - $specialDepreciationTotalRate > 0.000001) {
            throw new InvalidArgumentException('Special depreciation distribution must not exceed total rate.');
        }
    }

    public function capitalizableAcquisitionCosts(): float
    {
        return $this->acquisitionCost + $this->capitalizableAncillaryCosts;
    }

    public function depreciationBasis(): float
    {
        return max(0.0, $this->capitalizableAcquisitionCosts() - $this->iabReductionAmount);
    }

    public function depreciationStartYear(): int
    {
        return (int)$this->depreciationStart->format('Y');
    }

    public function depreciationStartMonth(): int
    {
        return (int)$this->depreciationStart->format('n');
    }
}
