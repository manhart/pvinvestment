<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain\Tax;

final class TaxYearResult
{
    public function __construct(
        public readonly int $year,
        public readonly float $taxableResultBeforeLoss,
        public readonly float $lossUsed,
        public readonly float $lossCreated,
        public readonly float $lossCarriedForward,
        public readonly float $taxableResultAfterLoss,
        public readonly float $taxAmount,
        public readonly int $taxCashflowYear,
        public readonly int $taxCashflowMonth,
    ) {}
}
