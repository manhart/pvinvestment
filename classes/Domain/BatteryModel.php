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
    ): self {
        return new self(
            model: self::MODEL_FULL_OWNERSHIP,
            annualRevenue: $annualRevenue,
            annualOperatingCosts: $annualOperatingCosts,
            sharingModel: RevenueSharingModel::fullInvestorOwnership(),
            marketAccessFee: $marketAccessFee,
            optimizerFee: $optimizerFee,
            batteryCapex: $batteryCapex,
        );
    }

    public static function profitSharing(
        float $annualRevenue,
        float $annualOperatingCosts,
        RevenueSharingModel $sharingModel,
        float $marketAccessFee = 0.0,
        float $optimizerFee = 0.0,
        float $batteryCapex = 0.0,
    ): self {
        return new self(
            model: self::MODEL_PROFIT_SHARING,
            annualRevenue: $annualRevenue,
            annualOperatingCosts: $annualOperatingCosts,
            sharingModel: $sharingModel,
            marketAccessFee: $marketAccessFee,
            optimizerFee: $optimizerFee,
            batteryCapex: $batteryCapex,
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
     *     sharingBase: string
     * }
     */
    public function annualAllocation(): array
    {
        return $this->annualEconomics()->toAllocationArray();
    }

    public function annualEconomics(): BatteryInvestorEconomics
    {
        $grossRevenue = $this->annualRevenue;
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
        );
    }
}
