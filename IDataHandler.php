<?php

namespace RangelReale\mdh;

/**
 * Interface IDataHandler
 */
interface IDataHandler
{
    const FORMAT_NONE = 0;
    const FORMAT_SHORT = 1;
    const FORMAT_MEDIUM = 2;
    const FORMAT_LONG = 3;
    const FORMAT_FULL = 4;
    const FORMAT_INPUT = 10;
    
    // convert the value from handler format to PHP format
    public function parse($value, $options);
    
    // convert the value from php format to handler format
    public function format($value, $options);
}
