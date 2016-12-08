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

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_converters['default'] = new DefaultConverter($this);
    }
    
    /**
     * Convert the value to PHP format using the converter
     * 
     * @param string $converter converter name
     * @param string $datatype data type
     * @param mixed $value value
     * @param array $options options
     * @return mixed parsed value in PHP format
     * @throws InvalidConverterException
     */
    public function parse($converter, $datatype, $value, $options = [])
    {
        if ($converter == '') $converter = 'default';
        $options = array_merge($options, ['__converter'=>$converter]);
        
        if (isset($this->_converters[$converter])) {
            if ($this->_converters[$converter]->canConvert($datatype))
                return $this->_converters[$converter]->parse($datatype, $value, $options, $this);
            return $this->_converters['default']->parse($datatype, $value, $options, $this);
        }
        throw new InvalidConverterException($converter);
    }
    
    /**
     * Convert the value from PHP format using the converter
     * 
     * @param string $converter converter name
     * @param string $datatype data type
     * @param mixed $value value
     * @param array $options options
     * @return mixed formatted value
     * @throws InvalidConverterException
     */
    public function format($converter, $datatype, $value, $options = [])
    {
        if ($converter == '') $converter = 'default';
        $options = array_merge($options, ['__converter'=>$converter]);
        
        if (isset($this->_converters[$converter])) {
            if ($this->_converters[$converter]->canConvert($datatype))
                return $this->_converters[$converter]->format($datatype, $value, $options, $this);
            return $this->_converters['default']->format($datatype, $value, $options, $this);
        }
        throw new InvalidConverterException($converter);
    }
    
    /**
     * Convert the value from one converter to another
     * 
     * @param string|null $converterFrom converter name or null to not parse the value
     * @param string|null $converterTo converter name or null to not format the value
     * @param string $datatype data type
     * @param mixed $value value
     * @param array $optionsFrom options to parse
     * @param array $optionsTo options to format
     * @return mixed converted value
     */
    public function convert($converterFrom, $converterTo, $datatype, $value, $optionsFrom = [], $optionsTo = [])
    {
        if ($converterFrom !== null)
            $value = $this->parse($converterFrom, $datatype, $value, $optionsFrom);
        if ($converterTo !== null)
            $value = $this->format($converterTo, $datatype, $value, $optionsTo);
        return $value;
    }
    
    /**
     * Gets a converter
     * 
     * @param string $name converter name
     * @return IConverter converter
     */
    public function getConverter($name)
    {
        return $this->_converters[$name];
    }
    
    // 
    /**
     * Adds a converter
     * 
     * @param string $name converter name
     * @param IConverter $converter converter
     */
    public function addConverter($name, $converter)
    {
        $this->_converters[$name] = $converter;
    }
    
    /**
     * Gets the locale
     * 
     * @return string
     */
    public function getLocale()
    {
        return 'en-US';
    }
    
    /**
     * Get converter handler as property
     * 
     * @param string $name converter name
     * @return BaseMDHConverter converter handler
     */
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
    
    public function parse($datatype, $value, $options = [])
    {
        return $this->_mdh->parse($this->_converter, $datatype, $value, $options);
    }
    
    public function format($datatype, $value, $options = [])
    {
        return $this->_mdh->format($this->_converter, $datatype, $value, $options);
    }
}