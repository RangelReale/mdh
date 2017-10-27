<?php

namespace RangelReale\mdh\test;

use PHPUnit\Framework\TestCase;
use RangelReale\mdh\MDH;

class UserConverterTest extends TestCase
{
    public function createMDH()
    {
        $mdh = new MDH();
        $mdh->locale = 'en-US';
        return $mdh;
    }

    public function testConverterFormatRaw()
    {
        $mdh = $this->createMDH();
        $this->assertEquals($mdh->format('user', 'raw', 998), 998);
    }
 
    public function testConverterFormatBoolean()
    {
        $mdh = $this->createMDH();
        $this->assertTrue($mdh->format('user', 'boolean', true) == '1');
    }
    
}