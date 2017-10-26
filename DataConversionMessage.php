<?php

namespace RangelReale\mdh;

use RangelReale\mdh\base\Object;

class DataConversionMessage extends Object
{
    public function getMessage($datatype, $parseOrFormat, $value, $options, $extra)
    {
        $sval = '';
        try
        {
            $sval = strval($value);
        } catch (Exception $e) {
            $sval = '<value>';
        }
        return 'Invalid "'.$datatype.'" datatype "'.$parseOrFormat.'" conversion: '.$sval;;
    }
}