<?php

namespace RangelReale\mdh;

/**
 * Data handler that just returns the value without any processing
 */
class DataHandler_NOP implements IDataHandler
{
    public function parse($value, $options)
    {
        return $value;
    }
    
    public function format($value, $options)
    {
        return $value;
    }
}
