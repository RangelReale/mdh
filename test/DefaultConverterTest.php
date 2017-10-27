<?php

namespace RangelReale\mdh\test;

use PHPUnit\Framework\TestCase;
use RangelReale\mdh\MDH;

class DefaultConverterTest extends TestCase
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
        $this->assertEquals($mdh->format('default', 'raw', 998), 998);
    }

    public function testConverterFormatBoolean()
    {
        $mdh = $this->createMDH();
        $this->assertTrue($mdh->format('default', 'boolean', true) === '1');
    }

    public function testConverterFormatInteger()
    {
        $mdh = $this->createMDH();
        $this->assertEquals($mdh->format('default', 'integer', '2998'), 2998);
    }

    public function testConverterFormatDecimal()
    {
        $mdh = $this->createMDH();
        $this->assertEquals($mdh->format('default', 'decimal', 15.332), '15.33');
    }
    
    public function testConverterFormatDatetime()
    {
        $mdh = $this->createMDH();
        $dt = new \DateTime();
        $dt->setTimezone(new \DateTimeZone('UTC'));
        $dt->setDate(2017, 10, 01)->setTime(15, 0, 0);
        $this->assertEquals($mdh->format('default', 'datetime', $dt), 1506870000);
    }
    
    public function testConverterFormatBytes()
    {
        $mdh = $this->createMDH();
        $this->assertEquals($mdh->format('default', 'bytes', 1000), '1.00 KB');
    }
    
    public function testConverterFormatTimeperiod()
    {
        $mdh = $this->createMDH();
        $this->assertEquals($mdh->format('default', 'timeperiod', 220), '00:03:40');
    }

    public function testConverterFormatBitmask()
    {
        $mdh = $this->createMDH();
        $this->assertEquals($mdh->format('default', 'bitmask', 20), [2, 4]);
    }
    
}