<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain;

use InvalidArgumentException;

final class BatteryModel
{
    public const MODEL_NONE = 'none';
    public const MODEL_FULL_OWNERSHIP = 'full_ownership';
    public const MODEL_PROFIT_SHARING = 'profit_sharing';

    private function __construct(
        public readonly string $model,
        public readonly float $annualRevenue,
        public readonly float $annualOperatingCosts,
        public readonly RevenueSharingModel $sharingModel,
        public readonly float $marketAccessFee = 0.0,
        public readonly float $optimizerFee = 0.0,
        public readonly float $batteryCapex = 0.0,
        public readonly ?int $capexPaymentYear = null,
        public readonly ?int $capexPaymentMonth = null,
        public readonly float $batteryDegradationRatePerYear = 0.0,
        public readonly bool $batteryReplacementEnabled = false,
        public readonly ?int $batteryReplacementYear = null,
        public readonly int $batteryReplacementMonth = 1,
        public readonly float $batteryReplacementCost = 0.0,
        public readonly ?float $investorReplacementCostShare = null,
        public readonly ?float $operatorReplacementCostShare = null,
    ) {
        if($annualRevenue < 0.0) {
            throw new InvalidArgumentException('Annual battery revenue must not be negative.');
        }
        if($annualOperatingCosts < 0.0) {
            throw new InvalidArgumentException('Annual battery operating costs must not be negative.');
        }
        if($marketAccessFee < 0.0) {
            throw new InvalidArgumentException('Market access fee must not be negative.');
        }
        if($optimizerFee < 0.0) {
            throw new InvalidArgumentException('Optimizer fee must not be negative.');
        }
        if($batteryCapex < 0.0) {
            throw new InvalidArgumentException('Battery capex must not be negative.');
        }
        if($capexPaymentMonth !== null && ($capexPaymentMonth < 1 || $capexPaymentMonth > 12)) {
            throw new InvalidArgumentException('Battery capex payment month must be between 1 and 12.');
        }
        if($batteryDegradationRatePerYear < 0.0 || $batteryDegradationRatePerYear > 1.0) {
            throw new InvalidArgumentException('Battery degradation rate per year must be between 0 and 1.');
        }
        if($batteryReplacementMonth < 1 || $batteryReplacementMonth > 12) {
            throw new InvalidArgumentException('Battery replacement month must be between 1 and 12.');
        }
        if($batteryReplacementCost < 0.0) {
            throw new InvalidArgumentException('Battery replacement cost must not be negative.');
        }
        if($batteryReplacementEnabled && $batteryReplacementYear === null) {
            throw new InvalidArgumentException('Battery replacement year is required when replacement is enabled.');
        }
        if($investorReplacementCostShare !== null) {
            $this->assertShare($investorReplacementCostShare, 'Investor replacement cost share');
        }
        if($operatorReplacementCostShare !== null) {
            $this->assertShare($operatorReplacementCostShare, 'Operator replacement cost share');
        }
        if($investorReplacementCostShare !== null && $operatorReplacementCostShare !== null
            && abs(($investorReplacementCostShare + $operatorReplacementCostShare) - 1.0) > 0.000001) {
            throw new InvalidArgumentException('Replacement cost shares must sum to 1.');
        }
    }

    public static function none(): self
    {
        return new self(
            model: self::MODEL_NONE,
            annualRevenue: 0.0,
            annualOperatingCosts: 0.0,
            sharingModel: RevenueSharingModel::fullInvestorOwnership(),
        );
    }

    public static function fullOwnership(
        float $annualRevenue,
        float $annualOperatingCosts = 0.0,
        float $marketAccessFee = 0.0,
        float $optimizerFee = 0.0,
        float $batteryCapex = 0.0,
        ?int $capexPaymentYear = null,
        ?int $capexPaymentMonth = null,
        float $batteryDegradationRatePerYear = 0.0,
        bool $batteryReplacementEnabled = false,
        ?int $batteryReplacementYear = null,
        int $batteryReplacementMonth = 1,
        float $batteryReplacementCost = 0.0,
        ?float $investorReplacementCostShare = null,
        ?float $operatorReplacementCostShare = null,
    ): self {
        return new self(
            model: self::MODEL_FULL_OWNERSHIP,
            annualRevenue: $annualRevenue,
            annualOperatingCosts: $annualOperatingCosts,
            sharingModel: RevenueSharingModel::fullInvestorOwnership(),
            marketAccessFee: $marketAccessFee,
            optimizerFee: $optimizerFee,
            batteryCapex: $batteryCapex,
            capexPaymentYear: $capexPaymentYear,
            capexPaymentMonth: $capexPaymentMonth,
            batteryDegradationRatePerYear: $batteryDegradationRatePerYear,
            batteryReplacementEnabled: $batteryReplacementEnabled,
            batteryReplacementYear: $batteryReplacementYear,
            batteryReplacementMonth: $batteryReplacementMonth,
            batteryReplacementCost: $batteryReplacementCost,
            investorReplacementCostShare: $investorReplacementCostShare,
            operatorReplacementCostShare: $operatorReplacementCostShare,
        );
    }

    public static function profitSharing(
        float $annualRevenue,
        float $annualOperatingCosts,
        RevenueSharingModel $sharingModel,
        float $marketAccessFee = 0.0,
        float $optimizerFee = 0.0,
        float $batteryCapex = 0.0,
        ?int $capexPaymentYear = null,
        ?int $capexPaymentMonth = null,
        float $batteryDegradationRatePerYear = 0.0,
        bool $batteryReplacementEnabled = false,
        ?int $batteryReplacementYear = null,
        int $batteryReplacementMonth = 1,
        float $batteryReplacementCost = 0.0,
        ?float $investorReplacementCostShare = null,
        ?float $operatorReplacementCostShare = null,
    ): self {
        return new self(
            model: self::MODEL_PROFIT_SHARING,
            annualRevenue: $annualRevenue,
            annualOperatingCosts: $annualOperatingCosts,
            sharingModel: $sharingModel,
            marketAccessFee: $marketAccessFee,
            optimizerFee: $optimizerFee,
            batteryCapex: $batteryCapex,
            capexPaymentYear: $capexPaymentYear,
            capexPaymentMonth: $capexPaymentMonth,
            batteryDegradationRatePerYear: $batteryDegradationRatePerYear,
            batteryReplacementEnabled: $batteryReplacementEnabled,
            batteryReplacementYear: $batteryReplacementYear,
            batteryReplacementMonth: $batteryReplacementMonth,
            batteryReplacementCost: $batteryReplacementCost,
            investorReplacementCostShare: $investorReplacementCostShare,
            operatorReplacementCostShare: $operatorReplacementCostShare,
        );
    }

    /**
     * @return array{
     *     grossRevenue: float,
     *     marketAccessFee: float,
     *     optimizerFee: float,
     *     batteryOpex: float,
     *     netRevenue: float,
     *     netMargin: float,
     *     investorRevenue: float,
     *     operatorRevenue: float,
     *     investorCosts: float,
     *     operatorCosts: float,
     *     investorCapex: float,
     *     operatorCapex: float,
     *     sharingBase: string,
     *     degradationFactor: float,
     *     revenueBeforeDegradation: float,
     *     revenueAfterDegradation: float
     * }
     */
    public function annualAllocation(): array
    {
        return $this->annualEconomics()->toAllocationArray();
    }

    /**
     * @return array{
     *     grossRevenue: float,
     *     marketAccessFee: float,
     *     optimizerFee: float,
     *     batteryOpex: float,
     *     netRevenue: float,
     *     netMargin: float,
     *     investorRevenue: float,
     *     operatorRevenue: float,
     *     investorCosts: float,
     *     operatorCosts: float,
     *     investorCapex: float,
     *     operatorCapex: float,
     *     sharingBase: string,
     *     degradationFactor: float,
     *     revenueBeforeDegradation: float,
     *     revenueAfterDegradation: float
     * }
     */
    public function annualAllocationForYear(int $year, int $revenueStartYear): array
    {
        return $this->annualEconomicsForYear($year, $revenueStartYear)->toAllocationArray();
    }

    public function annualEconomics(): BatteryInvestorEconomics
    {
        return $this->economicsForGrossRevenue(
            grossRevenue: $this->annualRevenue,
            degradationFactor: 1.0,
            revenueBeforeDegradation: $this->annualRevenue,
        );
    }

    public function annualEconomicsForYear(int $year, int $revenueStartYear): BatteryInvestorEconomics
    {
        $degradationFactor = $this->degradationFactorForYear($year, $revenueStartYear);
        $grossRevenue = $this->annualRevenue * $degradationFactor;

        return $this->economicsForGrossRevenue(
            grossRevenue: $grossRevenue,
            degradationFactor: $degradationFactor,
            revenueBeforeDegradation: $this->annualRevenue,
        );
    }

    public function withAnnualRevenue(float $annualRevenue): self
    {
        return new self(
            model: $this->model,
            annualRevenue: $annualRevenue,
            annualOperatingCosts: $this->annualOperatingCosts,
            sharingModel: $this->sharingModel,
            marketAccessFee: $this->marketAccessFee,
            optimizerFee: $this->optimizerFee,
            batteryCapex: $this->batteryCapex,
            capexPaymentYear: $this->capexPaymentYear,
            capexPaymentMonth: $this->capexPaymentMonth,
            batteryDegradationRatePerYear: $this->batteryDegradationRatePerYear,
            batteryReplacementEnabled: $this->batteryReplacementEnabled,
            batteryReplacementYear: $this->batteryReplacementYear,
            batteryReplacementMonth: $this->batteryReplacementMonth,
            batteryReplacementCost: $this->batteryReplacementCost,
            investorReplacementCostShare: $this->investorReplacementCostShare,
            operatorReplacementCostShare: $this->operatorReplacementCostShare,
        );
    }

    public function degradationFactorForYear(int $year, int $revenueStartYear): float
    {
        if($this->batteryDegradationRatePerYear === 0.0) {
            return 1.0;
        }

        $yearsAfterRevenueStart = max(0, $year - $revenueStartYear);

        return (1.0 - $this->batteryDegradationRatePerYear) ** $yearsAfterRevenueStart;
    }

    public function investorReplacementCost(): float
    {
        return $this->batteryReplacementCost * $this->effectiveInvestorReplacementCostShare();
    }

    public function effectiveInvestorReplacementCostShare(): float
    {
        return $this->investorReplacementCostShare ?? $this->sharingModel->investorCapexShare;
    }

    private function economicsForGrossRevenue(float $grossRevenue, float $degradationFactor, float $revenueBeforeDegradation): BatteryInvestorEconomics
    {
        $netRevenue = $grossRevenue - $this->marketAccessFee - $this->optimizerFee;
        $netMargin = $netRevenue - $this->annualOperatingCosts;

        $sharingBaseAmount = match($this->sharingModel->sharingBase) {
            RevenueSharingModel::SHARING_BASE_GROSS_REVENUE => $grossRevenue,
            RevenueSharingModel::SHARING_BASE_NET_REVENUE => $netRevenue,
            RevenueSharingModel::SHARING_BASE_NET_MARGIN => $netMargin,
        };

        $allocatableCosts = match($this->sharingModel->sharingBase) {
            RevenueSharingModel::SHARING_BASE_GROSS_REVENUE => $this->marketAccessFee + $this->optimizerFee + $this->annualOperatingCosts,
            RevenueSharingModel::SHARING_BASE_NET_REVENUE => $this->annualOperatingCosts,
            RevenueSharingModel::SHARING_BASE_NET_MARGIN => 0.0,
        };

        return new BatteryInvestorEconomics(
            grossRevenue: $grossRevenue,
            marketAccessFee: $this->marketAccessFee,
            optimizerFee: $this->optimizerFee,
            batteryOpex: $this->annualOperatingCosts,
            netRevenue: $netRevenue,
            netMargin: $netMargin,
            investorRevenue: $sharingBaseAmount * $this->sharingModel->investorRevenueShare,
            operatorRevenue: $sharingBaseAmount * $this->sharingModel->operatorRevenueShare,
            investorCosts: $allocatableCosts * $this->sharingModel->investorCostShare,
            operatorCosts: $allocatableCosts * $this->sharingModel->operatorCostShare,
            investorCapex: $this->batteryCapex * $this->sharingModel->investorCapexShare,
            operatorCapex: $this->batteryCapex * $this->sharingModel->operatorCapexShare,
            sharingBase: $this->sharingModel->sharingBase,
            degradationFactor: $degradationFactor,
            revenueBeforeDegradation: $revenueBeforeDegradation,
            revenueAfterDegradation: $grossRevenue,
        );
    }

    private function assertShare(float $share, string $label): void
    {
        if($share < 0.0 || $share > 1.0) {
            throw new InvalidArgumentException($label.' must be between 0 and 1.');
        }
    }
}
