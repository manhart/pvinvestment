<?php
declare(strict_types=1);

namespace pvinvestment;

use pvinvestment\classes\PvInvestmentApp;
use pvinvestment\guis\GUI_Frame\GUI_Frame;
use Throwable;

require_once __DIR__.'/config/bootstrap.php';

$App = PvInvestmentApp::getInstance();

try {
    $App->setup([
        'application.name' => 'pvinvestment',
        'application.title' => 'PV-Investitionsrechner',
        'application.launchModule' => GUI_Frame::class,
    ]);

    $App->render();
}
catch(Throwable $e) {
    throw $e;
}

