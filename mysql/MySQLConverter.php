<?php

namespace RangelReale\mdh\mysql;

use RangelReale\mdh\BaseConverter;
use RangelReale\mdh\IDataHandler;
use RangelReale\mdh\Util;
use RangelReale\mdh\DataConversionException;

/**
 * Class MySQLConverter
 */
class MySQLConverter extends BaseConverter
{
    public function __construct($mdh)
    {
        parent::__construct($mdh);

        $this->setHandler('date', ['RangelReale\mdh\mysql\MySQLDataHandler_Datetime', Util::CREATEOBJECT_THIS, 'date']);
        $this->setHandler('time', ['RangelReale\mdh\mysql\MySQLDataHandler_Datetime', Util::CREATEOBJECT_THIS, 'time']);
        $this->setHandler('datetime', ['RangelReale\mdh\mysql\MySQLDataHandler_Datetime', Util::CREATEOBJECT_THIS, 'datetime']);
    }
}

class MySQLDataHandler_Datetime implements IDataHandler
{
    private $_converter;
    private $_type;
    
    private $_type_format = [
        'date' => 'Y-m-d',
        'time' => 'H:i:s',
        'datetime' => 'Y-m-d H:i:s',
    ];
    
    public function __construct($converter, $type)
    {
        $this->_converter = $converter;
        $this->_type = $type;
    }
    
    public function parse($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        $ret = \DateTime::createFromFormat($this->_type_format[$this->_type], $value);
        if ($ret === false) {
            $this->_converter->mdh()->throwDataConversionException($this->_type, 'parse', $value, $options);
        }
        return $ret;
    }
    
    public function format($value, $options)
    {
        if ($value === null || $value == '')
            return null;
        $value = Util::formatToDateTime($value);
        if ($value === false) {
            $this->_converter->mdh()->throwDataConversionException($this->_type, 'format', $value, $options);
        }
        return $value->format($this->_type_format[$this->_type]);
    }
}