<?php

namespace RangelReale\mdh\user;

use RangelReale\mdh\IDataHandler;

/**
 * Class UserConverterLocale
 */
class UserConverterLocale
{
    public function getDateFormat($type)
    {
        return new UserConverterLocaleTimeFormat('', $type);
    }
    
    public function getTimeFormat($type)
    {
        return new UserConverterLocaleTimeFormat('', IDataHandler::FORMAT_NONE, $type);
    }
    
    public function getDateTimeFormat($type)
    {
        return new UserConverterLocaleTimeFormat('', $type, $type);
    }
    
    public function parseBoolean($value, $options)
    {
        return $value == true;
    }
    
    public function formatBoolean($value, $options)
    {
        return (string)$value;
    }
}
