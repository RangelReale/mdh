<?php

namespace RangelReale\mdh\user;

use RangelReale\mdh\base\BaseObject;
use RangelReale\mdh\IDataHandler;

/**
 * Class UserConverterLocale
 */
class UserConverterLocale extends BaseObject
{
    private $_mdh;

    public function __construct($mdh, $config = [])
    {
        $this->_mdh = $mdh;
        parent::__construct($config);
    }
    
    /**
     * @param IDataHandler::FORMAT_XXX $format
     * @return UserConverterLocaleTimeFormat|array(UserConverterLocaleTimeFormat)
     */
    public function getDateFormat($format)
    {
        return new UserConverterLocaleTimeFormat('', $format);
    }
    
    /**
     * @param IDataHandler::FORMAT_XXX $format
     * @return UserConverterLocaleTimeFormat|array(UserConverterLocaleTimeFormat)
     */
    public function getTimeFormat($format)
    {
        return new UserConverterLocaleTimeFormat('', IDataHandler::FORMAT_NONE, $format);
    }
    
    /**
     * @param IDataHandler::FORMAT_XXX $format
     * @return UserConverterLocaleTimeFormat|array(UserConverterLocaleTimeFormat)
     */
    public function getDateTimeFormat($format)
    {
        return new UserConverterLocaleTimeFormat('', $format, $format);
    }

    /**
     * Parse boolean string
     */
    public function parseBoolean($value, $options)
    {
        return $value == true;
    }
    
    /**
     * Format boolean value
     */
    public function formatBoolean($value, $options)
    {
        return (string)$value;
    }
}
