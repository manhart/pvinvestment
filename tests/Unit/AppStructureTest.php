<?php
declare(strict_types=1);

namespace pvinvestment\tests\Unit;

use PHPUnit\Framework\TestCase;
use pvinvestment\classes\PvInvestmentApp;
use pvinvestment\classes\Form\ScenarioFormData;
use pvinvestment\classes\Form\ScenarioFormMapper;
use pvinvestment\classes\Form\ScenarioFormValidator;
use pvinvestment\guis\GUI_PvInvestment\GUI_PvInvestment;
use pvinvestment\guis\GUI_Calculator\GUI_Calculator;
use pvinvestment\guis\GUI_Frame\GUI_Frame;

final class AppStructureTest extends TestCase
{
    public function testApplicationClassesAreAutoloadable(): void
    {
        self::assertTrue(class_exists(PvInvestmentApp::class));
        self::assertTrue(class_exists(GUI_Frame::class));
        self::assertTrue(class_exists(GUI_Calculator::class));
        self::assertTrue(class_exists(GUI_PvInvestment::class));
        self::assertTrue(class_exists(ScenarioFormData::class));
        self::assertTrue(class_exists(ScenarioFormValidator::class));
        self::assertTrue(class_exists(ScenarioFormMapper::class));
    }
}
