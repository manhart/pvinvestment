<?php
declare(strict_types=1);

namespace pvinvestment\classes;

use pool\classes\Core\Weblication;

final class PvInvestmentApp extends Weblication
{
    public function setup(array $settings = []): static
    {
        parent::setup($settings);
        return $this;
    }
}

