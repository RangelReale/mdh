<?php

namespace RangelReale\mdh\test;

use PHPUnit\Framework\TestCase;
use RangelReale\mdh\MDH;
use RangelReale\mdh\mysql\MySQLConverter;

class MySQLConverterTest extends TestCase
{
    public function createMDH()
    {
        $mdh = new MDH();
        $mdh->locale = 'en-US';
        $mdh->setConverter('mysql', MySQLConverter::className());
        return $mdh;
    }

    public function testConverterFormatDatetime()
    {
        $mdh = $this->createMDH();
        $this->assertEquals($mdh->format('mysql', 'datetime', 1506870000), '2017-10-01 15:00:00');
    }
}