<?php

namespace RangelReale\mdh\mysql;

use RangelReale\mdh\IConverter;
use RangelReale\mdh\IDataHandler;
use RangelReale\mdh\Util;
use RangelReale\mdh\InvalidDataHandlerException;
use RangelReale\mdh\DataConversionException;

/**
 * Class MySQLConverter
 */
class MySQLConverter implements IConverter
{
    private $_mdh;
    private $_datahandlers = [];
    
    public function __construct($mdh)
    {
        $this->_mdh = $mdh;
        
        $this->_datahandlers['date'] = new MySQLDataHandler_Datetime('date');
        $this->_datahandlers['time'] = new MySQLDataHandler_Datetime('time');
        $this->_datahandlers['datetime'] = new MySQLDataHandler_Datetime('datetime');
    }
    
    public function canConvert($datatype)
    {
        return isset($this->_datahandlers[$datatype]);
    }
    
    public function parse($datatype, $value, $options = [])
    {
        if (isset($this->_datahandlers[$datatype]))
            return $this->_datahandlers[$datatype]->parse($value, $options);
        
        throw new InvalidDataHandlerException($datatype);
    }
    
    public function format($datatype, $value, $options = [])
    {
        if (isset($this->_datahandlers[$datatype]))
            return $this->_datahandlers[$datatype]->format($value, $options);
        
        throw new InvalidDataHandlerException($datatype);
    }
    
    public function addHandler($datatype, $handler)
    {
        $this->_datahandlers[$datatype] = $handler;
    }
}

class MySQLDataHandler_Datetime implements IDataHandler
{
    private $_type;
    
    private $_type_format = [
        'date' => 'Y-m-d',
        'time' => 'H:i:s',
        'datetime' => 'Y-m-d H:i:s',
    ];
    
    public function __construct($type)
    {
        $this->_type = $type;
    }
    
    public function parse($value, $options)
    {
        $ret = \DateTime::createFromFormat($this->_type_format[$this->_type], $value);
        if ($ret === false)
            throw new DataConversionException($this->_type, 'parse', $value);
        return $ret;
    }
    
    public function format($value, $options)
    {
        $value = Util::formatToDateTime($value, $this->_type);
        return $value->format($this->_type_format[$this->_type]);
    }
}