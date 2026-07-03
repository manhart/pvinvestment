<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain;

use InvalidArgumentException;
use pvinvestment\classes\Domain\Tax\TaxLossHandlingStrategy;

final class TaxAssumptions
{
    public function __construct(
        public readonly float $annualTaxPayment = 0.0,
        public readonly int $calculationYear = 2026,
        public readonly float $incomeTaxRate = 0.0,
        public readonly float $acquisitionCost = 0.0,
        public readonly float $capitalizableAncillaryCosts = 0.0,
        public readonly float $immediatelyDeductibleCosts = 0.0,
        public readonly int $depreciationStartYear = 2026,
        public readonly int $depreciationStartMonth = 1,
        public readonly string $depreciationMethod = self::DEPRECIATION_LINEAR,
        public readonly float $linearDepreciationRate = 0.0,
        public readonly float $decliningDepreciationRate = 0.0,
        public readonly bool $iabEnabled = false,
        public readonly float $iabAmount = 0.0,
        public readonly int $iabDeductionYear = 2025,
        public readonly int $iabAdditionYear = 2026,
        public readonly bool $specialDepreciationEnabled = false,
        public readonly float $specialDepreciationRate = 0.0,
        public readonly int $specialDepreciationStartYear = 2026,
        public readonly int $specialDepreciationYears = 1,
        public readonly int $taxPaymentDelayYears = 0,
        public readonly string $lossHandlingStrategy = TaxLossHandlingStrategy::IMMEDIATE,
        public readonly float $maxLossCarryBackAmount = 0.0,
        public readonly int $maxLossCarryBackYears = 1,
        public readonly array $manualUsableLossByYear = [],
        public readonly int $taxPaymentDelayMonths = 0,
        public readonly array $taxRateByYear = [],
    ) {
        if($incomeTaxRate < 0.0 || $incomeTaxRate > 1.0) {
            throw new InvalidArgumentException('Income tax rate must be between 0 and 1.');
        }
        foreach([
            'acquisitionCost' => $acquisitionCost,
            'capitalizableAncillaryCosts' => $capitalizableAncillaryCosts,
            'immediatelyDeductibleCosts' => $immediatelyDeductibleCosts,
            'linearDepreciationRate' => $linearDepreciationRate,
            'decliningDepreciationRate' => $decliningDepreciationRate,
            'iabAmount' => $iabAmount,
            'specialDepreciationRate' => $specialDepreciationRate,
            'maxLossCarryBackAmount' => $maxLossCarryBackAmount,
        ] as $label => $value) {
            if($value < 0.0) {
                throw new InvalidArgumentException($label.' must not be negative.');
            }
        }
        if($depreciationStartMonth < 1 || $depreciationStartMonth > 12) {
            throw new InvalidArgumentException('Depreciation start month must be between 1 and 12.');
        }
        if(!in_array($depreciationMethod, [self::DEPRECIATION_LINEAR, self::DEPRECIATION_DECLINING], true)) {
            throw new InvalidArgumentException('Unsupported depreciation method.');
        }
        if($specialDepreciationYears < 1) {
            throw new InvalidArgumentException('Special depreciation years must be at least 1.');
        }
        if($taxPaymentDelayYears < 0) {
            throw new InvalidArgumentException('Tax payment delay years must not be negative.');
        }
        TaxLossHandlingStrategy::assertValid($lossHandlingStrategy);
        if($maxLossCarryBackYears < 0) {
            throw new InvalidArgumentException('Max loss carry-back years must not be negative.');
        }
        if($taxPaymentDelayMonths < 0) {
            throw new InvalidArgumentException('Tax payment delay months must not be negative.');
        }
        foreach($manualUsableLossByYear as $year => $amount) {
            if(!is_int($year)) {
                throw new InvalidArgumentException('Manual usable loss years must be integer calendar years.');
            }
            if($amount < 0.0) {
                throw new InvalidArgumentException('Manual usable loss amounts must not be negative.');
            }
        }
        foreach($taxRateByYear as $year => $rate) {
            if(!is_int($year)) {
                throw new InvalidArgumentException('Tax rate years must be integer calendar years.');
            }
            if($rate < 0.0 || $rate > 1.0) {
                throw new InvalidArgumentException('Tax rates by year must be between 0 and 1.');
            }
        }
    }

    public const DEPRECIATION_LINEAR = 'linear';
    public const DEPRECIATION_DECLINING = 'declining';

    public function usesComputedTaxModel(): bool
    {
        return $this->incomeTaxRate > 0.0
            || $this->acquisitionCost > 0.0
            || $this->capitalizableAncillaryCosts > 0.0
            || $this->immediatelyDeductibleCosts > 0.0
            || $this->iabEnabled
            || $this->specialDepreciationEnabled
            || $this->taxRateByYear !== [];
    }

    public function taxRateForYear(int $year): float
    {
        return $this->taxRateByYear[$year] ?? $this->incomeTaxRate;
    }
}
