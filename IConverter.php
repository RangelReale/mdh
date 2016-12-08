<?php

namespace RangelReale\mdh;

/**
 * Interface IConverter
 */
interface IConverter
{
    // convert the value from handler format to PHP format
    public function parse($datatype, $value, $options);
    
    // convert the value from php format to handler format
    public function format($datatype, $value, $options);
    
    // check if it is possible to convert this data type
    public function canConvert($datatype);
    
    // add a handler for a data type
    public function addHandler($datatype, $handler);
}