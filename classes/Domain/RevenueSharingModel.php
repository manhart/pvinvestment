<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain;

use InvalidArgumentException;

final class RevenueSharingModel
{
    public const SHARING_BASE_GROSS_REVENUE = 'gross_revenue';
    public const SHARING_BASE_NET_REVENUE = 'net_revenue';
    public const SHARING_BASE_NET_MARGIN = 'net_margin';

    public function __construct(
        public readonly float $investorRevenueShare,
        public readonly float $operatorRevenueShare,
        public readonly float $investorCostShare,
        public readonly float $operatorCostShare,
        public readonly string $sharingBase = self::SHARING_BASE_GROSS_REVENUE,
        public readonly float $investorCapexShare = 1.0,
        public readonly float $operatorCapexShare = 0.0,
    ) {
        self::assertShare($investorRevenueShare, 'Investor revenue share');
        self::assertShare($operatorRevenueShare, 'Operator revenue share');
        self::assertShare($investorCostShare, 'Investor cost share');
        self::assertShare($operatorCostShare, 'Operator cost share');
        self::assertShare($investorCapexShare, 'Investor capex share');
        self::assertShare($operatorCapexShare, 'Operator capex share');
        self::assertShareSum($investorRevenueShare, $operatorRevenueShare, 'Revenue shares');
        self::assertShareSum($investorCostShare, $operatorCostShare, 'Cost shares');
        self::assertShareSum($investorCapexShare, $operatorCapexShare, 'Capex shares');
        self::assertSharingBase($sharingBase);
    }

    public static function fullInvestorOwnership(): self
    {
        return new self(
            investorRevenueShare: 1.0,
            operatorRevenueShare: 0.0,
            investorCostShare: 1.0,
            operatorCostShare: 0.0,
            sharingBase: self::SHARING_BASE_GROSS_REVENUE,
            investorCapexShare: 1.0,
            operatorCapexShare: 0.0,
        );
    }

    public static function profitSharing(
        float $investorRevenueShare,
        float $operatorRevenueShare,
        string $sharingBase = self::SHARING_BASE_GROSS_REVENUE,
        float $investorCostShare = 1.0,
        float $operatorCostShare = 0.0,
        float $investorCapexShare = 1.0,
        float $operatorCapexShare = 0.0,
    ): self {
        return new self(
            investorRevenueShare: $investorRevenueShare,
            operatorRevenueShare: $operatorRevenueShare,
            investorCostShare: $investorCostShare,
            operatorCostShare: $operatorCostShare,
            sharingBase: $sharingBase,
            investorCapexShare: $investorCapexShare,
            operatorCapexShare: $operatorCapexShare,
        );
    }

    private static function assertShare(float $share, string $label): void
    {
        if($share < 0.0 || $share > 1.0) {
            throw new InvalidArgumentException($label.' must be between 0 and 1.');
        }
    }

    private static function assertShareSum(float $firstShare, float $secondShare, string $label): void
    {
        if(abs(($firstShare + $secondShare) - 1.0) > 0.000001) {
            throw new InvalidArgumentException($label.' must sum to 1.');
        }
    }

    private static function assertSharingBase(string $sharingBase): void
    {
        if(!in_array($sharingBase, [
            self::SHARING_BASE_GROSS_REVENUE,
            self::SHARING_BASE_NET_REVENUE,
            self::SHARING_BASE_NET_MARGIN,
        ], true)) {
            throw new InvalidArgumentException('Unknown battery sharing base: '.$sharingBase);
        }
    }
}
