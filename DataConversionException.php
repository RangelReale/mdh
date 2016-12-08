<?php

namespace RangelReale\mdh;

/**
 * Exception DataConversionException
 */
class DataConversionException extends MDHException
{
    public function __construct($data, $parseOrFormat, $value)
    {
        $sval = '';
        try
        {
            $sval = strval($value);
        } catch (Exception $e) {
            $sval = '<value>';
        }
        parent::__construct('Invalid "'.$data.'" data "'.$parseOrFormat.'" conversion: '.$sval);
    }
}