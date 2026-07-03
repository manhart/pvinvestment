<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain\Tax;

use InvalidArgumentException;
use pvinvestment\classes\Calculators\DepreciationCalculator;

final class TaxAssetLedger
{
    /**
     * @param list<TaxAsset> $assets
     */
    public function __construct(
        public readonly array $assets,
    ) {
        if(!$assets) {
            throw new InvalidArgumentException('Tax asset ledger requires at least one asset.');
        }
        foreach($assets as $asset) {
            if(!$asset instanceof TaxAsset) {
                throw new InvalidArgumentException('Tax asset ledger assets must contain TaxAsset instances.');
            }
        }
    }

    public function capitalizableAcquisitionCosts(): float
    {
        return array_sum(array_map(
            static fn(TaxAsset $asset): float => $asset->capitalizableAcquisitionCosts(),
            $this->assets,
        ));
    }

    public function depreciationBasis(): float
    {
        return array_sum(array_map(
            static fn(TaxAsset $asset): float => $asset->depreciationBasis(),
            $this->assets,
        ));
    }

    public function depreciationForYear(int $year, DepreciationCalculator $calculator): DepreciationYear
    {
        $openingBookValue = 0.0;
        $regularDepreciation = 0.0;
        $specialDepreciation = 0.0;
        $closingBookValue = 0.0;
        $depreciationMonths = 0;
        $methodsUsed = [];

        foreach($this->assets as $asset) {
            $assetYear = $calculator->calculate($asset, $year, $year)->year($year);
            $openingBookValue += $assetYear->openingBookValue;
            $regularDepreciation += $assetYear->regularDepreciation;
            $specialDepreciation += $assetYear->specialDepreciation;
            $closingBookValue += $assetYear->closingBookValue;
            $depreciationMonths += $assetYear->depreciationMonths;
            $methodsUsed[$assetYear->methodUsed] = true;
        }

        return new DepreciationYear(
            year: $year,
            assetName: 'ledger',
            openingBookValue: $openingBookValue,
            regularDepreciation: $regularDepreciation,
            specialDepreciation: $specialDepreciation,
            closingBookValue: $closingBookValue,
            depreciationMonths: $depreciationMonths,
            methodUsed: count($methodsUsed) === 1 ? array_key_first($methodsUsed) : 'mixed',
        );
    }
}
