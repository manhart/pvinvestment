<?php
declare(strict_types=1);

namespace pvinvestment\guis\GUI_Calculator;

use pool\classes\GUI\GUI_Module;

final class GUI_Calculator extends GUI_Module
{
    protected array $templates = [
        'stdout' => 'tpl_calculator.html',
    ];

    protected function prepare(): void
    {
        $this->Template->setVar('MODULE_ID', $this->getName());
    }
}

