<?php

namespace RangelReale\mdh;

use RangelReale\mdh\def\DefaultConverter;
use RangelReale\mdh\user\UserConverter;

/**
 * Class BaseMDH
 */
class BaseMDH
{
    private $_converters = [];
    private $_convertersProp = [];
    private $_dataconversionmessage;
    private $_datatypealiases = [];

    public $dateFormat = IDataHandler::FORMAT_SHORT;
    public $timeFormat = IDataHandler::FORMAT_SHORT;
    public $dateTimeFormat = IDataHandler::FORMAT_SHORT;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_dataconversionmessage = new DataConversionMessage;
        $this->initConverters();
    }
    
    protected function initConverters()
    {
        $this->_converters['default'] = new DefaultConverter($this);
        $this->_converters['user'] = new UserConverter($this);
        
        $this->addDataTypeAlias('decimalfull', 'decimal');
    }
    
    /**
     * Convert the value to PHP format using the converter
     * 
     * @param string $converter converter name. If null, return $value unparsed. If '', use 'default' converter
     * @param string $datatype data type
     * @param mixed $value value
     * @param array $options options
     * @return mixed parsed value in PHP format
     * @throws InvalidConverterException
     */
    public function parse($converter, $datatype, $value, $options = [])
    {
        if ($converter === null)
            return $value;
        if ($converter == '') 
            $converter = 'default';
        $options = array_merge($options, ['__converter'=>$converter]);
        
        if (isset($this->_converters[$converter])) {
            foreach ($this->getDataTypeAliasesFor($datatype) as $curdatatype) {
                if ($this->_converters[$converter]->canConvert($curdatatype))
                    return $this->_converters[$converter]->parse($curdatatype, $value, $options, $this);
            }
            return $this->_converters['default']->parse($datatype, $value, $options, $this);
        }
        throw new InvalidConverterException($converter);
    }
    
    /**
     * Convert the value from PHP format using the converter
     * 
     * @param string $converter converter name. If null, return $value unparsed. If '', use 'default' converter
     * @param string $datatype data type
     * @param mixed $value value
     * @param array $options options
     * @return mixed formatted value
     * @throws InvalidConverterException
     */
    public function format($converter, $datatype, $value, $options = [])
    {
        if ($converter === null)
            return $value;
        if ($converter == '') 
            $converter = 'default';
        $options = array_merge($options, ['__converter'=>$converter]);
        
        if (isset($this->_converters[$converter])) {
            foreach ($this->getDataTypeAliasesFor($datatype) as $curdatatype) {
                if ($this->_converters[$converter]->canConvert($curdatatype))
                    return $this->_converters[$converter]->format($curdatatype, $value, $options, $this);
            }
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
     * Convert multiple fields at the same time
     * @param string $converterFrom From converter
     * @param string $converterTo To converter
     * @param \RangelReale\mdh\MultiConversion $multiConversion 
     * @param array $values
     * @return \RangelReale\mdh\MultiConversionResult
     * @throws \RangelReale\mdh\DataConversionException
     */
    public function convertMulti($converterFrom, $converterTo, $multiConversion, $values)
    {
        $ret = new MultiConversionResult();
        foreach ($values as $vname => $vvalue) {
            if (isset($multiConversion->attributes[$vname])) {
                if (is_array($multiConversion->attributes[$vname])) {
                    $dataType = isset($multiConversion->attributes[$vname]['dataType'])?$multiConversion->attributes[$vname]['dataType']:'raw';
                    $options = isset($multiConversion->attributes[$vname]['options'])?$multiConversion->attributes[$vname]['options']:[];
                    $optionsFrom = isset($multiConversion->attributes[$vname]['optionsFrom'])?$multiConversion->attributes[$vname]['optionsFrom']:$options;
                    $optionsTo = isset($multiConversion->attributes[$vname]['optionsTo'])?$multiConversion->attributes[$vname]['optionsTo']:$options;
                } else {
                    $dataType = $multiConversion->attributes[$vname];
                    $options = [];
                    $optionsFrom = [];
                    $optionsTo = [];
                }

                try {
                    $vvalue = $this->convert($converterFrom, $converterTo, $dataType, $vvalue, $optionsFrom, $optionsTo);
                } catch (DataConversionException $e) {
                    if ($multiConversion->throwErrors) {
                        throw $e;
                    }
                    $ret->hasErrors = true;
                    $ret->errors[$vname] = $e;
                    $vvalue = $e;
                }
            }
            $ret->result[$vname]=$vvalue;
        }
        return $ret;
    }

    /**
     * Convert an array of multiple fields at the same time
     * @param string $converterFrom From converter
     * @param string $converterTo To converter
     * @param \RangelReale\mdh\MultiConversion $multiConversion 
     * @param array $list
     * @return \RangelReale\mdh\MultiConversionResult
     * @throws \RangelReale\mdh\DataConversionException
     */
    public function convertMultiList($converterFrom, $converterTo, $multiConversion, $list)
    {
        $ret = new MultiConversionResult();
        foreach ($list as $listindex => $listitem) {
            $r = $this->convertMulti($converterFrom, $converterTo, $multiConversion, $listitem);
            if ($r->hasErrors) {
                $ret->hasErrors = true;
                $ret->errors[$listindex] = $r->errors;
            }
            $ret->result[] = $r->result;
        }
        return $ret;
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
    public function setConverter($name, $converter)
    {
        $this->_converters[$name] = $converter;
    }
    
    /**
     * Adds a data type alias
     * 
     * @param string $datatype From datatype
     * @param string $alias To datatype
     */
    public function addDataTypeAlias($datatype, $alias)
    {
        if (!isset($this->_datatypealiases[$datatype])) {
            $this->_datatypealiases[$datatype] = [];
        }
        $this->_datatypealiases[$datatype][]=$alias;
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
    
    public function getDataConversionMessage()
    {
        return $this->_dataconversionmessage;
    }
    
    /**
     * @param DataConversionMessage $dataconversionmessage
     */
    public function setDataConversionMessage($dataconversionmessage)
    {
        $this->_dataconversionmessage = $dataconversionmessage;
    }
    
    /**
     * Throws a data converstion exception
     */
    public function throwDataConversionException($datatype, $parseOrFormat, $value, $options, $extra = '')
    {
        throw new DataConversionException($this->getDataConversionMessage()->getMessage($datatype, $parseOrFormat, $value, $options, $extra),
            $datatype, $parseOrFormat, $value, $options, $extra);
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
    
    private function getDataTypeAliasesFor($datatype)
    {
        $ret = [$datatype];
        if (isset($this->_datatypealiases[$datatype]))
            $ret = array_merge($ret, $this->_datatypealiases[$datatype]);
        return $ret;
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