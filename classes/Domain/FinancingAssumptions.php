<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain;

use InvalidArgumentException;

final class FinancingAssumptions
{
    public function __construct(
        public readonly float $annualInterest = 0.0,
        public readonly float $annualRepayment = 0.0,
    ) {
        if($annualInterest < 0.0) {
            throw new InvalidArgumentException('Annual interest must not be negative.');
        }
        if($annualRepayment < 0.0) {
            throw new InvalidArgumentException('Annual repayment must not be negative.');
        }
    }

    public function annualDebtService(): float
    {
        return $this->annualInterest + $this->annualRepayment;
    }

    public function interestForMonthFactor(float $monthFactor): float
    {
        return $this->annualInterest * $monthFactor;
    }

    public function repaymentForMonthFactor(float $monthFactor): float
    {
        return $this->annualRepayment * $monthFactor;
    }
}
