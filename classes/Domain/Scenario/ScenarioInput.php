<?php
declare(strict_types=1);

namespace pvinvestment\classes\Domain\Scenario;

use InvalidArgumentException;
use pvinvestment\classes\Domain\BatteryModel;
use pvinvestment\classes\Domain\FinancingAssumptions;
use pvinvestment\classes\Domain\ProjectTimingAssumptions;
use pvinvestment\classes\Domain\PvAssumptions;
use pvinvestment\classes\Domain\RevenueSharingModel;
use pvinvestment\classes\Domain\SavingsPlanAssumptions;
use pvinvestment\classes\Domain\TaxAssumptions;

final class ScenarioInput
{
    public function __construct(
        public readonly string $id,
        public readonly string $description,
        public readonly PvAssumptions $pvAssumptions,
        public readonly BatteryModel $batteryModel,
        public readonly RevenueSharingModel $revenueSharingModel,
        public readonly FinancingAssumptions $financingAssumptions,
        public readonly TaxAssumptions $taxAssumptions,
        public readonly SavingsPlanAssumptions $savingsPlanAssumptions,
        public readonly int $durationYears,
        public readonly ?ProjectTimingAssumptions $timingAssumptions = null,
    ) {
        if($id === '') {
            throw new InvalidArgumentException('Scenario id must not be empty.');
        }
        if($durationYears < 1) {
            throw new InvalidArgumentException('Scenario duration must be at least one year.');
        }
    }
}

