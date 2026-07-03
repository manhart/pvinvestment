<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain;

final class BatteryInvestorEconomics
{
    public function __construct(
        public readonly float $grossRevenue,
        public readonly float $marketAccessFee,
        public readonly float $optimizerFee,
        public readonly float $batteryOpex,
        public readonly float $netRevenue,
        public readonly float $netMargin,
        public readonly float $investorRevenue,
        public readonly float $operatorRevenue,
        public readonly float $investorCosts,
        public readonly float $operatorCosts,
        public readonly float $investorCapex,
        public readonly float $operatorCapex,
        public readonly string $sharingBase,
    ) {}

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
    public function toAllocationArray(): array
    {
        return [
            'grossRevenue' => $this->grossRevenue,
            'marketAccessFee' => $this->marketAccessFee,
            'optimizerFee' => $this->optimizerFee,
            'batteryOpex' => $this->batteryOpex,
            'netRevenue' => $this->netRevenue,
            'netMargin' => $this->netMargin,
            'investorRevenue' => $this->investorRevenue,
            'operatorRevenue' => $this->operatorRevenue,
            'investorCosts' => $this->investorCosts,
            'operatorCosts' => $this->operatorCosts,
            'investorCapex' => $this->investorCapex,
            'operatorCapex' => $this->operatorCapex,
            'sharingBase' => $this->sharingBase,
        ];
    }
}
