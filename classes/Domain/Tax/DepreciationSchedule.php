<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain\Tax;

use InvalidArgumentException;

final class DepreciationSchedule
{
    /**
     * @param list<DepreciationYear> $years
     */
    public function __construct(
        public readonly TaxAsset $asset,
        public readonly array $years,
    ) {
        foreach($years as $year) {
            if(!$year instanceof DepreciationYear) {
                throw new InvalidArgumentException('Depreciation schedule years must contain DepreciationYear instances.');
            }
        }
    }

    public function year(int $year): DepreciationYear
    {
        foreach($this->years as $depreciationYear) {
            if($depreciationYear->year === $year) {
                return $depreciationYear;
            }
        }

        throw new InvalidArgumentException('Depreciation year not found: '.$year);
    }
}
