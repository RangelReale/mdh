<?php

namespace RangelReale\mdh;

use RangelReale\mdh\def\DefaultConverter;

/**
 * Class MDH
 */
class MDH
{
    private $_converters = [];

    public $locale = 'en-US';
    public $dateFormat = IDataHandler::FORMAT_SHORT;
    public $timeFormat = IDataHandler::FORMAT_SHORT;
    public $dateTimeFormat = IDataHandler::FORMAT_SHORT;
    
    public function __construct()
    {
        $this->_converters['default'] = new DefaultConverter($this);
    }
    
    // convert the value from handler format to PHP format
    public function parse($converter, $data, $value, $options = [])
    {
        if ($converter == '') $converter = 'default';
        $options = array_merge($options, ['__converter'=>$converter]);
        
        if (isset($this->_converters[$converter])) {
            if ($this->_converters[$converter]->canConvert($data))
                return $this->_converters[$converter]->parse($data, $value, $options, $this);
            return $this->_converters['default']->parse($data, $value, $options, $this);
        }
        throw new InvalidConverterException($converter);
    }
    
    // convert the value from php format to handler format
    public function format($converter, $data, $value, $options = [])
    {
        if ($converter == '') $converter = 'default';
        $options = array_merge($options, ['__converter'=>$converter]);
        
        if (isset($this->_converters[$converter])) {
            if ($this->_converters[$converter]->canConvert($data))
                return $this->_converters[$converter]->format($data, $value, $options, $this);
            return $this->_converters['default']->format($data, $value, $options, $this);
        }
        throw new InvalidConverterException($converter);
    }

    // convert the value from one converter to another
    public function convert($converterFrom, $converterTo, $data, $value, $optionsFrom = [], $optionsTo = [])
    {
        return $this->format($converterTo, $data, $this->parse($converterFrom, $data, $value, $optionsFrom), $optionsTo);
    }
    
    public function getConverter($name)
    {
        return $this->_converters[$name];
    }
    
    public function addConverter($name, $converter)
    {
        $this->_converters[$name] = $converter;
    }
}
