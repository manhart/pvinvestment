<?php
declare(strict_types=1);

namespace pvinvestment\tests\Unit;

use PHPUnit\Framework\TestCase;
use pvinvestment\classes\PvInvestmentApp;
use pvinvestment\guis\GUI_Calculator\GUI_Calculator;
use pvinvestment\guis\GUI_Frame\GUI_Frame;

final class AppStructureTest extends TestCase
{
    public function testApplicationClassesAreAutoloadable(): void
    {
        self::assertTrue(class_exists(PvInvestmentApp::class));
        self::assertTrue(class_exists(GUI_Frame::class));
        self::assertTrue(class_exists(GUI_Calculator::class));
    }
}

