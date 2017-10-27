<?php

namespace RangelReale\mdh\test;

use PHPUnit\Framework\TestCase;
use RangelReale\mdh\MDH;
use RangelReale\mdh\base\ObjectUtil;

class MDHTest extends TestCase
{
    public function testCreation()
    {
        $mdh = new MDH();
        $this->assertTrue($mdh instanceof MDH);
    }
    
    public function testObjectCreation()
    {
        $mdh = ObjectUtil::createObject([
            'class' => 'RangelReale\mdh\MDH',
        ]);
        $this->assertTrue($mdh instanceof MDH);
    }
}