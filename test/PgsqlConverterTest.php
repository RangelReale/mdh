<?php

namespace RangelReale\mdh\test;

use PHPUnit\Framework\TestCase;
use RangelReale\mdh\MDH;
use RangelReale\mdh\pgsql\PgsqlConverter;

class PgsqlConverterTest extends TestCase
{
    public function createMDH()
    {
        $mdh = new MDH();
        $mdh->locale = 'en-US';
        $mdh->setConverter('pgsql', PgsqlConverter::className());
        return $mdh;
    }

    public function testConverterFormatDatetime()
    {
        $mdh = $this->createMDH();
        $this->assertEquals($mdh->format('pgsql', 'datetime', 1506870000), '2017-10-01 15:00:00.000000+00:00');
    }
}