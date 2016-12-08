<?php

namespace RangelReale\mdh;

use RangelReale\mdh\def\DefaultConverter;

/**
 * Class BaseMDH
 */
class BaseMDH
{
    private $_converters = [];
    private $_convertersProp = [];

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
    
    // gets a converter
    public function getConverter($name)
    {
        return $this->_converters[$name];
    }
    
    // adds a new converter
    public function addConverter($name, $converter)
    {
        $this->_converters[$name] = $converter;
    }
    
    // gets the locale to use when needed
    public function getLocale()
    {
        return 'en-US';
    }
    
    // get converter as property
    public function __get($name)
    {
        if (isset($this->_converters[$name])) {
            if (!isset($this->_convertersProp[$name])) {
                $this->_convertersProp[$name] = new BaseMDHConverter($this, $name);
            }
            return $this->_convertersProp[$name];;
        }
        
        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }
}

// Helper to return converter as property
class BaseMDHConverter
{
    private $_mdh;
    private $_converter;
    
    public function __construct($mdh, $converter)
    {
        $this->_mdh = $mdh;
        $this->_converter = $converter;
    }
    
    public function parse($data, $value, $options = [])
    {
        return $this->_mdh->parse($this->_converter, $data, $value, $options);
    }
    
    public function format($data, $value, $options = [])
    {
        return $this->_mdh->format($this->_converter, $data, $value, $options);
    }
}