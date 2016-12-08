<?php

namespace RangelReale\mdh\user;

use RangelReale\mdh\IDataHandler;

/**
 * Class UserConverterLocaleTimeFormat
 */
class UserConverterLocaleTimeFormat
{
    public $dateFormat;
    public $timeFormat;
    public $pattern;
    
    public function __construct($pattern = '', $dateFormat = IDataHandler::FORMAT_NONE,
        $timeFormat = IDataHandler::FORMAT_NONE)
    {
        $this->dateFormat = $dateFormat;
        $this->timeFormat = $timeFormat;
        $this->pattern = $pattern;
    }
}