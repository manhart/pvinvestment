<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain\Tax;

use InvalidArgumentException;

final class TaxLossHandlingStrategy
{
    public const IMMEDIATE = 'immediate';
    public const CARRY_FORWARD = 'carry_forward';
    public const CARRY_BACK = 'carry_back';
    public const MANUAL = 'manual';
    public const NONE = 'none';

    public static function assertValid(string $strategy): void
    {
        if(!in_array($strategy, [
            self::IMMEDIATE,
            self::CARRY_FORWARD,
            self::CARRY_BACK,
            self::MANUAL,
            self::NONE,
        ], true)) {
            throw new InvalidArgumentException('Unsupported tax loss handling strategy: '.$strategy);
        }
    }
}
